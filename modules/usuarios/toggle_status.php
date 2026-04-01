?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$id = $_GET['id'] ?? null;
if (!$id || $id == $_SESSION['user_id']) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT activo FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$u = $stmt->fetch();

if ($u) {
    $new_status = $u['activo'] ? 0 : 1;
    $stmt_upd = $db->prepare("UPDATE usuarios SET activo = ? WHERE id = ?");
    $stmt_upd->execute([$new_status, $id]);
}

header('Location: index.php');
exit;
