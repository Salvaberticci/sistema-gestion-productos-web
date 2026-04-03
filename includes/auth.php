<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../vendor/autoload.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['user_rol'] !== 'admin') {
        header('Location: ' . APP_URL . '/modules/dashboard/index.php');
        exit;
    }
}

function isAdmin(): bool {
    return isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';
}

function login(string $username, string $password): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE username = ? AND activo = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nombre_completo'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_rol'] = $user['rol'];
        return true;
    }
    return false;
}

function logout(): void {
    session_destroy();
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

function getExchangeRate(): float {
    $db = getDB();
    $stmt = $db->query("SELECT valor FROM configuracion WHERE clave = 'tasa_cambio'");
    $row = $stmt->fetch();
    return $row ? (float)$row['valor'] : 1.0;
}

function formatCurrency(float $value, string $symbol = '$'): string {
    return $symbol . ' ' . number_format($value, 2, '.', ',');
}
