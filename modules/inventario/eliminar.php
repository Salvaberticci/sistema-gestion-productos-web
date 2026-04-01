?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$id = $_GET['id'] ?? null;
if ($id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM productos WHERE codigop = ?");
    $stmt->execute([$id]);
}
header('Location: index.php');
exit;
