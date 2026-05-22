<?php
declare(strict_types=1);

/**
 * Auto-create schema for the "Eligible Programs for Study Canada Loan
 * for Professional Courses" feature.
 *
 * - Creates the parrot_visa_cms DB if missing.
 * - Creates menu_items table (mirrors backend/database.sql) if missing.
 * - Creates eligible_programs_settings table for admin overrides.
 * - Seeds the public menu item pointing to /eligible-programs-canada-loan.
 *
 * Brochure data itself is owned by parrot_mis (DB: mis_parrot) and is
 * read cross-database in the model.
 */

require_once __DIR__ . '/../config/config.php';

if (!defined('PARROT_MIS_DB')) {
    define('PARROT_MIS_DB', getenv('PARROT_MIS_DB') ?: 'mis_parrot');
}
if (!defined('PARROT_MIS_PUBLIC_URL')) {
    define('PARROT_MIS_PUBLIC_URL', getenv('PARROT_MIS_PUBLIC_URL') ?: 'http://localhost/parrot_mis/');
}
if (!defined('ELIGIBLE_PROGRAMS_SLUG')) {
    define('ELIGIBLE_PROGRAMS_SLUG', '/eligible-programs-canada-loan');
}
if (!defined('ELIGIBLE_PROGRAMS_MENU_TITLE')) {
    define(
        'ELIGIBLE_PROGRAMS_MENU_TITLE',
        'Eligible Programs for Study Canada Loan for Professional Courses'
    );
}

/**
 * Connect to MySQL server WITHOUT a database first so we can CREATE DATABASE
 * if needed, then switch to it. Returns a PDO bound to the CMS database.
 */
function pcvc_eligible_programs_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $dbName = DB_NAME;
    $pdo->exec(
        'CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $dbName) . '`
         DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );
    $pdo->exec('USE `' . str_replace('`', '', $dbName) . '`');

    return $pdo;
}

/**
 * Create a table if it's missing, AND self-heal the common "exists in dictionary
 * but missing in engine" InnoDB state (errno 1932) that happens after a hard
 * MySQL crash or moving ibdata files without ibd files.
 *
 * @param string $tableName Bare table name (no backticks).
 * @param string $ddl       Full CREATE TABLE statement.
 */
function pcvc_eligible_programs_table(PDO $pdo, string $tableName, string $ddl): void
{
    $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
    try {
        $pdo->exec($ddl);
        $pdo->query('SELECT 1 FROM `' . $tableName . '` LIMIT 0');
        return;
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (
            stripos($msg, "doesn't exist in engine") !== false ||
            stripos($msg, 'tablespace is missing') !== false ||
            (int) $e->getCode() === 1932 ||
            (int) ($e->errorInfo[1] ?? 0) === 1932
        ) {
            @$pdo->exec('DROP TABLE IF EXISTS `' . $tableName . '`');
            $pdo->exec($ddl);
            return;
        }
        throw $e;
    }
}

function pcvc_eligible_programs_ensure_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $pdo = pcvc_eligible_programs_pdo();

    pcvc_eligible_programs_table($pdo, 'menu_items', "
        CREATE TABLE IF NOT EXISTS `menu_items` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(190) NOT NULL,
            `url` VARCHAR(255) NOT NULL,
            `parent_id` INT(11) DEFAULT 0,
            `order_index` INT(11) DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `icon_class` VARCHAR(100) DEFAULT NULL,
            `target` ENUM('_self','_blank') DEFAULT '_self',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    pcvc_eligible_programs_table($pdo, 'eligible_programs_settings', "
        CREATE TABLE IF NOT EXISTS `eligible_programs_settings` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `brochure_slug` VARCHAR(190) NOT NULL,
            `display_title` VARCHAR(255) NULL,
            `display_subtitle` VARCHAR(255) NULL,
            `is_featured` TINYINT(1) DEFAULT 0,
            `is_hidden` TINYINT(1) DEFAULT 0,
            `position` INT(11) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_eligible_brochure_slug` (`brochure_slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    try {
        $check = $pdo->prepare('SELECT id FROM menu_items WHERE url = :url LIMIT 1');
        $check->execute([':url' => ELIGIBLE_PROGRAMS_SLUG]);
        $exists = (bool) $check->fetch();
    } catch (PDOException $e) {
        $exists = false;
    }

    if (!$exists) {
        try {
            $next = (int) ($pdo->query(
                'SELECT COALESCE(MAX(order_index), 0) + 1 AS n
                 FROM menu_items WHERE parent_id = 0'
            )->fetch()['n'] ?? 1);
        } catch (PDOException $e) {
            $next = 1;
        }

        try {
            $ins = $pdo->prepare(
                'INSERT INTO menu_items (title, url, parent_id, order_index, is_active, icon_class, target)
                 VALUES (:title, :url, 0, :ord, 1, :icon, :target)'
            );
            $ins->execute([
                ':title'  => ELIGIBLE_PROGRAMS_MENU_TITLE,
                ':url'    => ELIGIBLE_PROGRAMS_SLUG,
                ':ord'    => $next,
                ':icon'   => 'GraduationCap',
                ':target' => '_self',
            ]);
        } catch (PDOException $e) {
            error_log('Eligible Programs menu seed failed: ' . $e->getMessage());
        }
    }

    $done = true;
}
