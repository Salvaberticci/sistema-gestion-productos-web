<?php
/**
 * Credenciales por defecto (desarrollo / XAMPP).
 * En produccion, config/database.local.php sobreescribe estos valores y NO se
 * versiona, de modo que un `git pull` nunca puede romper la conexion real.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'el_rebusque_web');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Sobrescritura local (credenciales reales del servidor). Ver .gitignore.
if (is_file(__DIR__ . '/database.local.php')) {
    require_once __DIR__ . '/database.local.php';
}

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
