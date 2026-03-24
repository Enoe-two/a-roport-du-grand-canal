<?php
// ── Lecture des variables Railway ────────────────────────────────────────────
// Railway injecte MYSQLHOST, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE, MYSQLPORT
// On supporte aussi MYSQL_URL (format mysql://user:pass@host:port/db)

$_db_host = getenv('MYSQLHOST')     ?: getenv('DB_HOST')     ?: '127.0.0.1';
$_db_user = getenv('MYSQLUSER')     ?: getenv('DB_USER')     ?: 'root';
$_db_pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS')     ?: '';
$_db_name = getenv('MYSQLDATABASE') ?: getenv('DB_NAME')     ?: 'railway';
$_db_port = getenv('MYSQLPORT')     ?: getenv('DB_PORT')     ?: '3306';

// Fallback : parser MYSQL_URL si les variables individuelles sont absentes
if (!getenv('MYSQLHOST') && !getenv('DB_HOST')) {
    $url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: '';
    if ($url) {
        $p = parse_url($url);
        $_db_host = $p['host'] ?? '127.0.0.1';
        $_db_user = $p['user'] ?? 'root';
        $_db_pass = $p['pass'] ?? '';
        $_db_name = ltrim($p['path'] ?? '/railway', '/');
        $_db_port = $p['port'] ?? '3306';
    }
}

define('DB_HOST', $_db_host);
define('DB_USER', $_db_user);
define('DB_PASS', $_db_pass);
define('DB_NAME', $_db_name);
define('DB_PORT', (string)$_db_port);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            // host= force TCP/IP (évite le socket Unix qui cause "No such file or directory")
            $dsn = "mysql:host=" . DB_HOST
                 . ";port=" . DB_PORT
                 . ";dbname=" . DB_NAME
                 . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 10,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Erreur de connexion BDD : ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
