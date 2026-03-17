<?php
// Support Railway (MYSQLHOST, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE, MYSQLPORT)
// et variables personnalisées (DB_HOST, DB_USER, DB_PASS, DB_NAME)
define('DB_HOST', getenv('MYSQLHOST')     ?: getenv('DB_HOST')     ?: 'localhost');
define('DB_USER', getenv('MYSQLUSER')     ?: getenv('DB_USER')     ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASS')     ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME')     ?: 'minecraft_airport');
define('DB_PORT', getenv('MYSQLPORT')     ?: getenv('DB_PORT')     ?: '3306');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Erreur de connexion BDD : ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
