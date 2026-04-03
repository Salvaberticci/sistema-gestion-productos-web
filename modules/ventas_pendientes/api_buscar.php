<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
$db = getDB();

try {
    $sql = "
        SELECT ov.*, c.nombre, c.apellido, c.cedula 
        FROM ordenes_venta ov 
        JOIN clientes c ON ov.cliente_id = c.id 
        WHERE ov.estado = 'pendiente'
    ";

    if (!empty($q)) {
        $sql .= " AND (c.nombre LIKE ? OR c.apellido LIKE ? OR c.cedula LIKE ? OR ov.id = ?)";
        $stmt = $db->prepare($sql . " ORDER BY ov.created_at ASC LIMIT 100");
        $stmt->execute(["%$q%", "%$q%", "%$q%", $q]);
    } else {
        $stmt = $db->query($sql . " ORDER BY ov.created_at ASC LIMIT 100");
    }

    $orders = $stmt->fetchAll();
    
    // Formatear datos para la respuesta
    foreach ($orders as &$o) {
        $o['cliente_full'] = htmlspecialchars($o['nombre'] . ' ' . $o['apellido']);
        $o['fecha_fmt'] = date('d/m/y H:i', strtotime($o['created_at']));
        $o['total_usd_fmt'] = formatCurrency((float)$o['total_usd']);
        $o['total_bs_fmt'] = formatCurrency((float)$o['total_bs'], 'Bs.');
    }

    echo json_encode(['success' => true, 'data' => $orders]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
