<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$id = $_GET['id'] ?? null;
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

if ($id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM productos WHERE codigop = ?");
        $stmt->execute([$id]);
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
    } catch (Exception $e) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

if (!$isAjax) {
    header('Location: index.php');
}
exit;
