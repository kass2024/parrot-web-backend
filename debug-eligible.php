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
$misDb   = getenv('PARROT_MIS_DB')   ?: 'mis_parrot';
$misUser = getenv('PARROT_MIS_USER') ?: '';
$misPass = getenv('PARROT_MIS_PASS') ?: '';
echo "  PARROT_MIS_DB   = " . $misDb . "\n";
echo "  PARROT_MIS_USER = " . ($misUser !== '' ? $misUser : "(blank — will reuse DB_USER)") . "\n";
echo "  PARROT_MIS_PASS = " . ($misPass !== '' ? "(set, " . strlen($misPass) . " chars)" : "(blank)") . "\n\n";

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

step("Connect to MIS DB ({$misDb}) as its own user", function () use (&$misPdo, $misDb, $misUser, $misPass) {
    $user = $misUser !== '' ? $misUser : DB_USER;
    $pass = $misUser !== '' ? $misPass : DB_PASS;
    $misPdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . $misDb . ';charset=utf8mb4',
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    return "connected as {$user}";
});

step("All databases this user can see", function () use ($cmsPdo) {
    if (!$cmsPdo) return 'skipped';
    $rows = $cmsPdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
    return implode(', ', $rows);
});

step("Brochures inside CMS DB itself", function () use ($cmsPdo) {
    if (!$cmsPdo) return 'skipped';
    try {
        $row = $cmsPdo->query('SELECT COUNT(*) FROM marketing_brochures')->fetchColumn();
        return (int) $row . " rows in " . DB_NAME . ".marketing_brochures (this is where MIS may be writing!)";
    } catch (Throwable $e) {
        return "(no marketing_brochures in " . DB_NAME . ")";
    }
});

step("MIS marketing_brochures exists", function () use (&$misPdo) {
    if (!$misPdo) return 'skipped';
    $row = $misPdo->query('SELECT COUNT(*) FROM marketing_brochures')->fetchColumn();
    return (int) $row . " brochures total";
});

step("MIS active brochures", function () use (&$misPdo) {
    if (!$misPdo) return 'skipped';
    $row = $misPdo->query('SELECT COUNT(*) FROM marketing_brochures WHERE is_active = 1')->fetchColumn();
    return (int) $row . " active";
});

step("MIS has university_id column", function () use (&$misPdo) {
    if (!$misPdo) return 'skipped';
    $stmt = $misPdo->query(
        "SELECT 1 FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME   = 'marketing_brochures'
            AND COLUMN_NAME  = 'university_id'
          LIMIT 1"
    );
    return $stmt && $stmt->fetch() ? "yes" : "NO -- the model will auto-add it; or run PART B SQL";
});

step("Sample brochure row", function () use (&$misPdo) {
    if (!$misPdo) return 'skipped';
    $stmt = $misPdo->query(
        "SELECT id, title, slug, is_active, region_id, pdf_path
         FROM marketing_brochures
         ORDER BY created_at DESC LIMIT 1"
    );
    $row  = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    return $row ?: '(no rows)';
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
