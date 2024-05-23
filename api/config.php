<?php

$config = parse_ini_file('../app/config/communication.ini');

$host = $config['host'];
$port = $config['port'];
$dbname = $config['name'];
$username = $config['user'];
$password = $config['pass'];

$dsn = "{$config['type']}:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}
