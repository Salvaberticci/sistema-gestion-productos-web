<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

header('Content-Type: application/json');
$db = getDB();

$id = $_GET['id'] ?? 0;
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID de orden no proporcionado']);
    exit;
}

try {
    // Info de la orden y cliente
    $stmt = $db->prepare("
        SELECT ov.*, c.nombre, c.apellido, ov.created_at as fecha
        FROM ordenes_venta ov
        JOIN clientes c ON ov.cliente_id = c.id
        WHERE ov.id = ?
    ");
    $stmt->execute([$id]);
    $orden = $stmt->fetch();

    if (!$orden) {
        echo json_encode(['success' => false, 'error' => 'Orden no encontrada']);
        exit;
    }

    // Items de la orden
    $stmt_items = $db->prepare("
        SELECT od.*, p.referencia 
        FROM ordenes_detalles od
        JOIN productos p ON od.producto_cod = p.codigop
        WHERE od.orden_id = ?
    ");
    $stmt_items->execute([$id]);
    $items = $stmt_items->fetchAll();

    echo json_encode([
        'success' => true,
        'orden' => $orden,
        'items' => $items
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
