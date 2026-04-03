<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

header('Content-Type: application/json');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $cedula = $_GET['cedula'] ?? '';
    if (!$cedula) {
        echo json_encode(['success' => false, 'error' => 'Cédula no proporcionada']);
        exit;
    }
    
    $stmt = $db->prepare("SELECT * FROM clientes WHERE cedula = ?");
    $stmt->execute([$cedula]);
    $cliente = $stmt->fetch();
    
    if ($cliente) {
        echo json_encode(['success' => true, 'cliente' => $cliente]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Payload JSON esperado
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $cedula = trim($data['cedula'] ?? '');
    $nombre = trim($data['nombre'] ?? '');
    $apellido = trim($data['apellido'] ?? '');
    $telefono = trim($data['telefono'] ?? '');
    
    if (empty($cedula) || empty($nombre) || empty($apellido) || empty($telefono)) {
        echo json_encode(['success' => false, 'error' => 'Todos los datos (Cédula, Nombre, Apellido, Teléfono) son obligatorios.']);
        exit;
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM clientes WHERE cedula = ?");
        $stmt->execute([$cedula]);
        $cliente = $stmt->fetch();
        
        if ($cliente) {
            // Actualizar si ya existe por si cambió número o letra del nombre
            $stmt_upd = $db->prepare("UPDATE clientes SET nombre=?, apellido=?, telefono=? WHERE cedula=?");
            $stmt_upd->execute([$nombre, $apellido, $telefono, $cedula]);
            $cliente['nombre'] = $nombre;
            $cliente['apellido'] = $apellido;
            $cliente['telefono'] = $telefono;
        } else {
            $stmt_in = $db->prepare("INSERT INTO clientes (cedula, nombre, apellido, telefono) VALUES (?, ?, ?, ?)");
            $stmt_in->execute([$cedula, $nombre, $apellido, $telefono]);
            $cliente_id = $db->lastInsertId();
            $cliente = [
                'id' => $cliente_id,
                'cedula' => $cedula,
                'nombre' => $nombre,
                'apellido' => $apellido,
                'telefono' => $telefono
            ];
        }
        echo json_encode(['success' => true, 'cliente' => $cliente]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database Error: ' . $e->getMessage()]);
    }
}
