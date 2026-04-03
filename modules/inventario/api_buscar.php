<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
$db = getDB();

if (empty($q)) {
    $stmt = $db->query("SELECT * FROM productos ORDER BY created_at DESC LIMIT 50");
} else {
    // 1. Aumentar métrica del usuario (Trazabilidad)
    if (isset($_SESSION['user_id'])) {
        $stmt_track = $db->prepare("UPDATE usuarios SET consultas_realizadas = consultas_realizadas + 1 WHERE id = ?");
        $stmt_track->execute([$_SESSION['user_id']]);
    }
    
    // 2. Aumentar popularidad del producto si escanean código exacto
    $stmt_prod_track = $db->prepare("UPDATE productos SET busquedas = busquedas + 1 WHERE codigop = ?");
    $stmt_prod_track->execute([$q]);

    $stmt = $db->prepare("SELECT * FROM productos WHERE codigop LIKE ? OR referencia LIKE ? ORDER BY busquedas DESC LIMIT 50");
    $stmt->execute(["%$q%", "%$q%"]);
}

$productos = $stmt->fetchAll();
$rate = getExchangeRate();

foreach ($productos as &$p) {
    $p['precio_bs'] = $p['pventa'] * $rate;
    $p['formatted_usd'] = formatCurrency((float)$p['pventa']);
    $p['formatted_bs'] = formatCurrency((float)$p['precio_bs'], 'Bs.');
    // Asegurar ruta de imagen
    if (empty($p['image_path'])) {
        $p['img_url'] = null;
    } else {
        $p['img_url'] = APP_URL . '/assets/images/products/' . basename($p['image_path']);
    }
}

echo json_encode($productos);
