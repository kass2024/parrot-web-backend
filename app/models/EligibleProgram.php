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
     * Uses the MIS connection (separate user) so no cross-grants are needed.
     */
    private function ensureMisColumns(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done   = true;
        $misPdo = pcvc_mis_pdo();
        if (!$misPdo) {
            return;
        }
        try {
            $check = $misPdo->query(
                "SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME   = 'marketing_brochures'
                    AND COLUMN_NAME  = 'university_id'
                  LIMIT 1"
            );
            if ($check && !$check->fetch()) {
                $misPdo->exec(
                    'ALTER TABLE `marketing_brochures`
                       ADD COLUMN `university_id` INT UNSIGNED NULL DEFAULT NULL AFTER `region_id`'
                );
                @$misPdo->exec(
                    'ALTER TABLE `marketing_brochures`
                       ADD INDEX `idx_brochure_university` (`university_id`)'
                );
            }
        } catch (Throwable $e) {
            error_log('ensureMisColumns failed: ' . $e->getMessage());
        }
    }

    /**
     * Read brochures from the MIS database (its own connection) and merge with
     * our eligible_programs_settings overrides in PHP. This avoids requiring
     * cross-database grants on cPanel.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPublishedBrochures(bool $includeHidden = false): array
    {
        $misPdo = pcvc_mis_pdo();
        if (!$misPdo) {
            error_log('EligibleProgram: MIS PDO unavailable (check PARROT_MIS_DB / PARROT_MIS_USER)');
            return [];
        }

        try {
            $cols = $misPdo->query(
                "SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME   = 'marketing_brochures'
                    AND COLUMN_NAME  = 'university_id'
                  LIMIT 1"
            );
            $hasUniversity = $cols && $cols->fetch();
        } catch (Throwable $e) {
            $hasUniversity = false;
        }

        $uniSelect  = $hasUniversity ? 'b.university_id, COALESCE(u.name, \'\') AS university_name' : 'NULL AS university_id, \'\' AS university_name';
        $uniJoin    = $hasUniversity ? 'LEFT JOIN universities u ON u.id = b.university_id' : '';

        $sql = "
            SELECT
                b.id, b.title, b.slug, b.description,
                b.pdf_filename, b.pdf_path, b.pdf_size_bytes, b.cover_image,
                b.view_count, b.share_count, b.created_at,
                b.region_id,
                COALESCE(r.name, 'Global') AS region_name,
                {$uniSelect}
            FROM marketing_brochures b
            LEFT JOIN regions r ON r.id = b.region_id
            {$uniJoin}
            WHERE b.is_active = 1
            ORDER BY b.created_at DESC
        ";

        try {
            $brochures = $misPdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('EligibleProgram MIS query failed: ' . $e->getMessage());
            return [];
        }

        $settings = [];
        if ($brochures) {
            $slugs = array_values(array_unique(array_map(
                static fn(array $b) => (string) ($b['slug'] ?? ''),
                $brochures
            )));
            $slugs = array_filter($slugs, static fn(string $s) => $s !== '');
            if ($slugs) {
                $placeholders = implode(',', array_fill(0, count($slugs), '?'));
                $stmt = $this->conn->prepare(
                    "SELECT brochure_slug, display_title, display_subtitle,
                            is_featured, is_hidden, position
                       FROM eligible_programs_settings
                      WHERE brochure_slug IN ({$placeholders})"
                );
                $stmt->execute(array_values($slugs));
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $settings[(string) $row['brochure_slug']] = $row;
                }
            }
        }

        $base = rtrim(PARROT_MIS_PUBLIC_URL, '/');
        $out  = [];
        foreach ($brochures as $row) {
            $slug = (string) ($row['slug'] ?? '');
            $s    = $settings[$slug] ?? [];
            $isHidden = (int) ($s['is_hidden'] ?? 0);
            if (!$includeHidden && $isHidden) {
                continue;
            }
            $row['display_title']    = (string) ($s['display_title'] ?? '') !== '' ? (string) $s['display_title'] : (string) $row['title'];
            $row['display_subtitle'] = (string) ($s['display_subtitle'] ?? '');
            $row['is_featured']      = (int) ($s['is_featured'] ?? 0);
            $row['is_hidden']        = $isHidden;
            $row['position']         = (int) ($s['position'] ?? 999999);
            $row['view_url']         = $base . '/brochure-view.php?slug=' . rawurlencode($slug);
            $row['pdf_url']          = $base . '/' . ltrim((string) $row['pdf_path'], '/');
            $row['pdf_size_human']   = $this->humanBytes((int) ($row['pdf_size_bytes'] ?? 0));
            $out[] = $row;
        }

        usort($out, static function (array $a, array $b): int {
            if ($a['is_featured'] !== $b['is_featured']) {
                return ((int) $b['is_featured']) <=> ((int) $a['is_featured']);
            }
            if ($a['position'] !== $b['position']) {
                return ((int) $a['position']) <=> ((int) $b['position']);
            }
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        return $out;
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
