<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit; }

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;
$accion = $data['accion'] ?? '';

if (!$id || !$accion) {
    echo json_encode(['success' => false, 'error' => 'Faltan parámetros']);
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();
    
    // Obtener items si es aprobacion para descontar stock
    if ($accion === 'aprobar') {
        $stmt_items = $db->prepare("SELECT * FROM ordenes_detalles WHERE orden_id = ?");
        $stmt_items->execute([$id]);
        $items = $stmt_items->fetchAll();
        
        foreach ($items as $it) {
            // Descontar inventario físico
            $upd = $db->prepare("UPDATE productos SET exisact = exisact - ? WHERE codigop = ?");
            $upd->execute([$it['cantidad'], $it['producto_cod']]);
        }
        
        $stmt_ord = $db->prepare("UPDATE ordenes_venta SET estado = 'completado' WHERE id = ?");
        $stmt_ord->execute([$id]);
        $message = "¡Venta aprobada e inventario actualizado!";
    } else {
        // Rechazar
        $stmt_ord = $db->prepare("UPDATE ordenes_venta SET estado = 'rechazado' WHERE id = ?");
        $stmt_ord->execute([$id]);
        $message = "La orden ha sido rechazada.";
    }
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => $message]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
