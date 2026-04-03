<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit; }

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['cliente_id']) || empty($data['items'])) {
    echo json_encode(['success' => false, 'error' => 'Faltan datos de la orden o el carrito está vacío']);
    exit;
}

$db = getDB();
$rate = getExchangeRate();

try {
    $db->beginTransaction();
    
    $total_usd = 0;
    foreach ($data['items'] as $item) {
        $total_usd += ($item['cantidad'] * $item['pventa']);
    }
    $total_bs = $total_usd * $rate;
    
    // Insert cabecera
    $stmt = $db->prepare("INSERT INTO ordenes_venta (usuario_id, cliente_id, estado, total_usd, total_bs, tasa_aplicada) VALUES (?, ?, 'pendiente', ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $data['cliente_id'], $total_usd, $total_bs, $rate]);
    $orden_id = $db->lastInsertId();
    
    // Insert lineas de detalle
    // Nota: El inventario FÍSICO NO se descuenta aquí. Se hará en el Dashboard del Administrador.
    $stmt_det = $db->prepare("INSERT INTO ordenes_detalles (orden_id, producto_cod, cantidad, precio_unitario_usd, subtotal_usd) VALUES (?, ?, ?, ?, ?)");
    foreach ($data['items'] as $item) {
        $subtotal = $item['cantidad'] * $item['pventa'];
        $stmt_det->execute([$orden_id, $item['codigop'], $item['cantidad'], $item['pventa'], $subtotal]);
    }
    
    // Anotar trazabilidad extra si fuese necesario (por ejemplo un contador de ordenes en la tabla usuarios? No, ya lo contamos con subquery, así que no hace falta sumar un contador).
    
    $db->commit();
    echo json_encode(['success' => true, 'orden_id' => $orden_id]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
