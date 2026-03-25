<?php
$_h = getenv('MYSQLHOST')     ?: getenv('DB_HOST')     ?: '127.0.0.1';
$_u = getenv('MYSQLUSER')     ?: getenv('DB_USER')     ?: 'root';
$_p = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS')     ?: '';
$_n = getenv('MYSQLDATABASE') ?: getenv('DB_NAME')     ?: 'railway';
$_o = getenv('MYSQLPORT')     ?: getenv('DB_PORT')     ?: '3306';

if (!$_h || $_h === 'localhost') {
    $url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: '';
    if ($url) {
        $p = parse_url($url);
        $_h = $p['host'] ?? '127.0.0.1';
        $_u = $p['user'] ?? 'root';
        $_p = $p['pass'] ?? '';
        $_n = ltrim($p['path'] ?? '/railway', '/');
        $_o = $p['port'] ?? '3306';
    }
}

define('DB_HOST', $_h);
define('DB_USER', $_u);
define('DB_PASS', $_p);
define('DB_NAME', $_n);
define('DB_PORT', (string)$_o);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 10,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die("<h1 style='font-family:sans-serif;color:#f56565;padding:2rem'>Erreur BDD : ".$e->getMessage()."</h1>");
        }
    }
    return $pdo;
}
