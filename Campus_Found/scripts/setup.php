<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$config = require dirname(__DIR__) . '/config.php';
$db = new PDO($config['dsn'], $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
    $schema = str_replace('INTEGER PRIMARY KEY AUTO_INCREMENT', 'INTEGER PRIMARY KEY AUTOINCREMENT', $schema);
    $db->exec('PRAGMA foreign_keys=ON');
}
$db->exec($schema);
echo "Schema installed.\n";
