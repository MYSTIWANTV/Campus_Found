<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$config = require dirname(__DIR__) . '/config.php';
$db = new PDO($config['dsn'], $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$name = trim(readline('Officer name: '));
$email = strtolower(trim(readline('Officer email: ')));
echo "Enter a password of at least 12 characters (input is visible on this local terminal).\n";
$password = readline('Password: ');
if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) { exit("Invalid input.\n"); }
$q = $db->prepare('INSERT INTO users(name,email,password,role,created_at) VALUES(?,?,?,?,?)');
$q->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),'security',gmdate('c')]);
echo "Security account created.\n";
