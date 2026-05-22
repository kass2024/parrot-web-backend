<?php
/**
 * Eligible Programs cPanel diagnostic
 * -----------------------------------
 * Upload this file to /backend/debug-eligible.php on the server and visit:
 *
 *     https://visaconsultantcanada.com/backend/debug-eligible.php
 *
 * It prints every step the production API takes and the exact error if any
 * of them fails. Delete this file once everything works -- it exposes config.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/app/config/config.php';

function step(string $label, callable $fn): void
{
    echo str_pad("[" . $label . "]", 38) . " ";
    try {
        $result = $fn();
        echo "OK";
        if ($result !== null) {
            echo "  -> " . (is_scalar($result) ? (string) $result : json_encode($result));
        }
        echo PHP_EOL;
    } catch (Throwable $e) {
        echo "FAIL  " . $e->getMessage() . PHP_EOL;
    }
}

echo "===========================================================\n";
echo " ELIGIBLE PROGRAMS DIAGNOSTIC\n";
echo " " . date('Y-m-d H:i:s') . "\n";
echo "===========================================================\n\n";

echo "ENV LOADED FROM " . realpath(__DIR__ . '/.env') . "\n";
echo "  DB_HOST       = " . DB_HOST . "\n";
echo "  DB_NAME       = " . DB_NAME . "\n";
echo "  DB_USER       = " . DB_USER . "\n";
echo "  DB_PASS       = " . (DB_PASS === '' ? "(empty)" : "(set, " . strlen(DB_PASS) . " chars)") . "\n";
$misDb = getenv('PARROT_MIS_DB') ?: 'mis_parrot';
echo "  PARROT_MIS_DB = " . $misDb . "\n\n";

$cmsPdo = null;
$misPdo = null;

step("Connect to CMS DB", function () use (&$cmsPdo) {
    $cmsPdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    return DB_NAME;
});

step("List CMS tables", function () use ($cmsPdo) {
    if (!$cmsPdo) return 'skipped';
    $rows = $cmsPdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    return count($rows) . " tables: " . implode(', ', $rows);
});

step("eligible_programs_settings exists", function () use ($cmsPdo) {
    if (!$cmsPdo) return 'skipped';
    $row = $cmsPdo->query('SELECT COUNT(*) FROM eligible_programs_settings')->fetchColumn();
    return (int) $row . " rows";
});

step("Connect to MIS DB ({$misDb})", function () use (&$misPdo, $misDb) {
    $misPdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . $misDb . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    return $misDb;
});

step("MIS marketing_brochures exists", function () use ($misPdo, $misDb) {
    if (!$misPdo) return 'skipped';
    $row = $misPdo->query('SELECT COUNT(*) FROM marketing_brochures')->fetchColumn();
    return (int) $row . " brochures total";
});

step("MIS active brochures", function () use ($misPdo) {
    if (!$misPdo) return 'skipped';
    $row = $misPdo->query('SELECT COUNT(*) FROM marketing_brochures WHERE is_active = 1')->fetchColumn();
    return (int) $row . " active";
});

step("MIS has university_id column", function () use ($misPdo, $misDb) {
    if (!$misPdo) return 'skipped';
    $stmt = $misPdo->prepare(
        'SELECT 1 FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = :db
            AND TABLE_NAME   = "marketing_brochures"
            AND COLUMN_NAME  = "university_id"
          LIMIT 1'
    );
    $stmt->execute([':db' => $misDb]);
    return $stmt->fetch() ? "yes" : "NO -- run the ALTER from sql/eligible_programs_cpanel.sql PART B";
});

step("Cross-DB join (the real query)", function () use ($cmsPdo, $misDb) {
    if (!$cmsPdo) return 'skipped';
    $sql = "SELECT COUNT(*) FROM `{$misDb}`.`marketing_brochures` b
            LEFT JOIN eligible_programs_settings s
                   ON s.brochure_slug COLLATE utf8mb4_general_ci = b.slug COLLATE utf8mb4_general_ci
            WHERE b.is_active = 1";
    $row = $cmsPdo->query($sql)->fetchColumn();
    return (int) $row . " brochures visible through the CMS user";
});

step("Sample brochure row via cross-DB", function () use ($cmsPdo, $misDb) {
    if (!$cmsPdo) return 'skipped';
    $sql = "SELECT b.id, b.title, b.slug, b.is_active, b.region_id, b.university_id, b.pdf_path
            FROM `{$misDb}`.`marketing_brochures` b
            ORDER BY b.created_at DESC LIMIT 1";
    $stmt = $cmsPdo->query($sql);
    $row  = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    return $row ? $row : '(no rows)';
});

echo "\n===========================================================\n";
echo " WHAT THE FRONT-END API ACTUALLY RETURNS\n";
echo "===========================================================\n";

require_once __DIR__ . '/app/controllers/BaseController.php';
require_once __DIR__ . '/app/controllers/EligibleProgramsController.php';

ob_start();
try {
    (new EligibleProgramsController())->apiList();
} catch (Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
$payload = ob_get_clean();
echo $payload . "\n";

echo "\nDONE. Delete this file when finished.\n";
