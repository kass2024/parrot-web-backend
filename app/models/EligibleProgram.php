<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../helpers/eligible_programs_schema.php';

class EligibleProgram extends BaseModel
{
    protected $table = 'eligible_programs_settings';
    protected $primaryKey = 'id';
    protected $fillable = [
        'brochure_slug', 'display_title', 'display_subtitle',
        'is_featured', 'is_hidden', 'position',
    ];

    public function __construct()
    {
        pcvc_eligible_programs_ensure_schema();
        parent::__construct();
        $this->ensureMisColumns();
    }

    /**
     * Make sure the parrot_mis `marketing_brochures` table has the columns we
     * read (e.g. university_id). Idempotent — safe to call every request.
     */
    private function ensureMisColumns(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $misDb = preg_replace('/[^a-zA-Z0-9_]/', '', PARROT_MIS_DB);
        if ($misDb === '') {
            $done = true;
            return;
        }
        try {
            $check = $this->conn->prepare(
                'SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = :db
                   AND TABLE_NAME = "marketing_brochures"
                   AND COLUMN_NAME = "university_id"
                 LIMIT 1'
            );
            $check->execute([':db' => $misDb]);
            if (!$check->fetch()) {
                $this->conn->exec(
                    "ALTER TABLE `{$misDb}`.`marketing_brochures`
                     ADD COLUMN `university_id` INT UNSIGNED NULL DEFAULT NULL AFTER `region_id`"
                );
                @$this->conn->exec(
                    "ALTER TABLE `{$misDb}`.`marketing_brochures`
                     ADD INDEX `idx_brochure_university` (`university_id`)"
                );
            }
        } catch (Throwable $e) {
            error_log('ensureMisColumns failed: ' . $e->getMessage());
        }
        $done = true;
    }

    /**
     * Cross-DB read from mis_parrot.marketing_brochures, merged with our
     * eligible_programs_settings overrides. Always returns absolute URLs that
     * point at the parrot_mis install (PDFs + public brochure-view.php page).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPublishedBrochures(bool $includeHidden = false): array
    {
        $misDb = preg_replace('/[^a-zA-Z0-9_]/', '', PARROT_MIS_DB);
        if ($misDb === '') {
            return [];
        }

        $sql = "
            SELECT
                b.id,
                b.title,
                b.slug,
                b.description,
                b.pdf_filename,
                b.pdf_path,
                b.pdf_size_bytes,
                b.cover_image,
                b.view_count,
                b.share_count,
                b.created_at,
                b.region_id,
                COALESCE(r.name, 'Global')             AS region_name,
                b.university_id,
                COALESCE(u.name, '')                   AS university_name,
                COALESCE(s.display_title, b.title)     AS display_title,
                COALESCE(s.display_subtitle, '')       AS display_subtitle,
                COALESCE(s.is_featured, 0)             AS is_featured,
                COALESCE(s.is_hidden, 0)               AS is_hidden,
                COALESCE(s.position, 999999)           AS position
            FROM `{$misDb}`.`marketing_brochures` b
            LEFT JOIN `{$misDb}`.`regions` r      ON r.id = b.region_id
            LEFT JOIN `{$misDb}`.`universities` u ON u.id = b.university_id
            LEFT JOIN `eligible_programs_settings` s
                   ON s.brochure_slug COLLATE utf8mb4_general_ci = b.slug COLLATE utf8mb4_general_ci
            WHERE b.is_active = 1
        ";

        if (!$includeHidden) {
            $sql .= ' AND COALESCE(s.is_hidden, 0) = 0';
        }

        $sql .= ' ORDER BY is_featured DESC, position ASC, b.created_at DESC';

        try {
            $rows = $this->query($sql);
        } catch (Throwable $e) {
            error_log('EligibleProgram cross-db query failed: ' . $e->getMessage());
            return [];
        }

        $base = rtrim(PARROT_MIS_PUBLIC_URL, '/');
        foreach ($rows as &$row) {
            $slug = (string) ($row['slug'] ?? '');
            $row['view_url'] = $base . '/brochure-view.php?slug=' . rawurlencode($slug);
            $row['pdf_url']  = $base . '/' . ltrim((string) $row['pdf_path'], '/');
            $row['pdf_size_human'] = $this->humanBytes((int) ($row['pdf_size_bytes'] ?? 0));
        }
        unset($row);

        return $rows;
    }

    public function setHidden(string $slug, bool $hidden): void
    {
        $this->upsertFlag($slug, ['is_hidden' => $hidden ? 1 : 0]);
    }

    public function setFeatured(string $slug, bool $featured): void
    {
        $this->upsertFlag($slug, ['is_featured' => $featured ? 1 : 0]);
    }

    public function setPosition(string $slug, int $position): void
    {
        $this->upsertFlag($slug, ['position' => $position]);
    }

    public function setDisplayLabels(string $slug, ?string $title, ?string $subtitle): void
    {
        $this->upsertFlag($slug, [
            'display_title'    => $title,
            'display_subtitle' => $subtitle,
        ]);
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function upsertFlag(string $slug, array $fields): void
    {
        $slug = trim($slug);
        if ($slug === '' || empty($fields)) {
            return;
        }

        $cols   = ['brochure_slug'];
        $params = [':brochure_slug' => $slug];
        $vals   = [':brochure_slug'];
        $update = [];

        foreach ($fields as $col => $val) {
            $col = preg_replace('/[^a-z_]/', '', $col);
            if ($col === '') {
                continue;
            }
            $cols[]              = $col;
            $vals[]              = ':' . $col;
            $params[':' . $col]  = $val;
            $update[]            = "`{$col}` = VALUES(`{$col}`)";
        }

        $sql = 'INSERT INTO eligible_programs_settings (' . implode(',', array_map(fn($c) => "`$c`", $cols)) . ')
                VALUES (' . implode(',', $vals) . ')
                ON DUPLICATE KEY UPDATE ' . implode(',', $update);

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
    }

    private function humanBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $i     = (int) floor(log($bytes, 1024));
        $i     = min($i, count($units) - 1);
        return round($bytes / (1024 ** $i), 1) . ' ' . $units[$i];
    }
}
