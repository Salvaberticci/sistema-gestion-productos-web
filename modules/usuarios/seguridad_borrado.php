<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$id_user_to_delete = $_POST['id'] ?? null;
$admin_password_input = $_POST['password'] ?? '';

if (!$id_user_to_delete || empty($admin_password_input)) {
    echo json_encode(['success' => false, 'error' => 'Faltan parámetros']);
    exit;
}

$db = getDB();

// 1. Obtener el hash del administrador actual para validarlo
$stmt_admin = $db->prepare("SELECT password FROM usuarios WHERE id = ?");
$stmt_admin->execute([$_SESSION['user_id']]);
$admin_data = $stmt_admin->fetch();

if (!$admin_data || !password_verify($admin_password_input, $admin_data['password'])) {
    echo json_encode(['success' => false, 'error' => 'Contraseña de administrador incorrecta']);
    exit;
}

// 2. Proteccion contra auto-borrado
if ($id_user_to_delete == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'error' => 'No puedes inactivar tu propia cuenta']);
    exit;
}

// 3. Ejecutar Soft Delete (inactivar)
try {
    $stmt_del = $db->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
    $stmt_del->execute([$id_user_to_delete]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()]);
}
