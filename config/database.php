<?php
/**
 * Credenciales de produccion (opcionales). Este archivo NO se versiona, por
 * lo que un `git pull` nunca puede romper la conexion real. Se carga primero
 * para que sus valores prevailan sobre los de desarrollo.
 */
if (is_file(__DIR__ . '/database.local.php')) {
    require_once __DIR__ . '/database.local.php';
}

// Valores por defecto para desarrollo (XAMPP). Solo se definen si faltan.
defined('DB_HOST')    || define('DB_HOST', 'localhost');
defined('DB_NAME')    || define('DB_NAME', 'el_rebusque_web');
defined('DB_USER')    || define('DB_USER', 'root');
defined('DB_PASS')    || define('DB_PASS', '');
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $pdo->exec("SET time_zone = '-04:00'");
        } catch (PDOException $e) {
            die("Error de conexion: " . $e->getMessage());
        }
    }
    return $pdo;
}
