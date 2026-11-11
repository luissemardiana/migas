<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
require_once __DIR__ . '/../app/bootstrap.php';
$db = database();
$db->exec("SET time_zone = '+07:00'");
foreach (explode(';', file_get_contents(__DIR__ . '/../database/participant-portal.sql')) as $sql) {
    if (trim($sql) !== '') $db->exec($sql);
}
$db->prepare('INSERT IGNORE INTO portal_migrations(version) VALUES (?)')->execute(['20261106_participant_portal']);
echo "Migrasi portal selesai; data lama dipertahankan.\n";
