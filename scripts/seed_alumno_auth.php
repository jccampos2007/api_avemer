<?php
/**
 * Seed alumno_auth table from existing alumnos.
 *
 * Usage: php8.5 scripts/seed_alumno_auth.php
 */

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$config = require __DIR__ . '/../src/Config/database.php';

$dsn = "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4";
$pdo = new PDO($dsn, $config['user'], $config['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Ensure table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS alumno_auth (
        id INT AUTO_INCREMENT PRIMARY KEY,
        alumno_id INT NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        email_verified_at DATETIME NULL,
        verification_token VARCHAR(64) NULL,
        remember_token VARCHAR(64) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (alumno_id) REFERENCES alumno(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Bulk insert in chunks for performance
$alumnos = $pdo->query("SELECT id, ci_pasapote FROM alumno ORDER BY id")->fetchAll();

$chunks = array_chunk($alumnos, 500);
$created = 0;
$skipped = 0;

$pdo->beginTransaction();

$checkStmt = $pdo->prepare("SELECT COUNT(*) FROM alumno_auth WHERE alumno_id = :id");
$insertStmt = $pdo->prepare("
    INSERT IGNORE INTO alumno_auth (alumno_id, password_hash)
    VALUES (:id, :hash)
");

// Use a fast hash for bulk seeding; real passwords will use bcrypt at login
// This is just a seed script - we hash with SHA256 for speed
foreach ($chunks as $chunk) {
    foreach ($chunk as $alumno) {
        $checkStmt->execute([':id' => $alumno['id']]);
        if ($checkStmt->fetchColumn() > 0) {
            $skipped++;
            continue;
        }

        $password = !empty($alumno['ci_pasapote']) ? $alumno['ci_pasapote'] : 'default';
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 8]);

        try {
            $insertStmt->execute([
                ':id' => $alumno['id'],
                ':hash' => $hash,
            ]);
            $created++;
        } catch (Exception $e) {
            // Skip errors
        }
    }
    $pdo->commit();
    $pdo->beginTransaction();
}

$pdo->commit();

echo "Creados: $created | Saltados: $skipped | Total: " . count($alumnos) . "\n";
