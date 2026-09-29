<?php
session_start();
date_default_timezone_set('America/Caracas');
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
        header('Location: ' . APP_URL . '/modules/ventas/index.php');
        exit;
    }
}

function isAdmin(): bool {
    return isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';
}

function isCajero(): bool {
    return isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'cajero';
}

function canViewOrders(): bool {
    return isAdmin() || isCajero();
}

function requireOrderAccess(): void {
    requireLogin();
    if (!canViewOrders()) {
        header('Location: ' . APP_URL . '/modules/ventas/index.php');
        exit;
    }
}

/**
 * Aprobar o rechazar ordenes. Ojo: aprobar descuenta el stock automaticamente,
 * asi que este permiso implica capacidad de modificar el inventario.
 */
function canApproveOrders(): bool {
    return isAdmin() || isCajero();
}

function requireApproveOrders(): void {
    requireLogin();
    if (!canApproveOrders()) {
        header('Location: ' . APP_URL . '/modules/ventas/index.php');
        exit;
    }
}

/**
 * Roles del sistema, en orden de menor a mayor privilegio.
 */
function etiquetasRol(): array {
    return [
        'empleado' => 'Empleado (Inventario e Impresión)',
        'cajero'   => 'Cajero (Ventas, consulta y aprobación de órdenes)',
        'admin'    => 'Administrador (Acceso Total)',
    ];
}

/**
 * Roles que la base de datos acepta realmente.
 * Si el ENUM de usuarios.rol todavia no incluye 'cajero', aplica la
 * migracion: MySQL trunca en silencio los valores no contemplados y el
 * usuario queda creado con el rol vacio.
 */
function rolesDisponibles(): array {
    static $roles = null;
    if ($roles !== null) {
        return $roles;
    }

    $roles = array_keys(etiquetasRol());

    try {
        $db = getDB();
        $col = $db->query("SHOW COLUMNS FROM usuarios LIKE 'rol'")->fetch();
        $tipo = (string)($col['Type'] ?? '');

        if ($tipo !== '' && stripos($tipo, "'cajero'") === false) {
            $db->exec("ALTER TABLE usuarios MODIFY rol ENUM('admin', 'empleado', 'cajero') NOT NULL DEFAULT 'empleado'");
        }
    } catch (Exception $e) {
        // Sin permisos DDL: no se ofrece el rol nuevo, pero el resto funciona
        $roles = ['admin', 'empleado'];
    }

    return $roles;
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
