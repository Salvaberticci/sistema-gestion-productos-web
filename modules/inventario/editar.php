?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: index.php'); exit; }

$db = getDB();
$stmt = $db->prepare("SELECT * FROM productos WHERE codigop = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { header('Location: index.php'); exit; }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ref = trim($_POST['referencia'] ?? '');
    $stock = (float)($_POST['exisact'] ?? 0);
    $price = (float)($_POST['pventa'] ?? 0);
    
    if (empty($ref)) {
        $error = 'La Referencia es obligatoria.';
    } else {
        // Manejo de Imagen
        $image_path = $p['image_path'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = $id . '_' . time() . '.' . $ext;
            $target = __DIR__ . '/../../assets/images/products/' . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $image_path = 'assets/images/products/' . $filename;
            }
        }

        try {
            $stmt = $db->prepare("UPDATE productos SET referencia = ?, exisact = ?, pventa = ?, image_path = ? WHERE codigop = ?");
            $stmt->execute([$ref, $stock, $price, $image_path, $id]);
            $success = 'Producto actualizado.';
            $stmt = $db->prepare("SELECT * FROM productos WHERE codigop = ?"); $stmt->execute([$id]); $p = $stmt->fetch();
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Editar Producto';
$currentModule = 'inventario';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-header">
    <h2 class="page-title">✏️ Editar: <?= htmlspecialchars($p['referencia']) ?></h2>
    <div class="flex gap-1">
        <?php if (isAdmin()): ?>
            <a href="eliminar.php?id=<?= $id ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que desea eliminar?')">🗑️</a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-secondary btn-sm">←</a>
    </div>
</div>

<div class="card">
    <?php if ($error): ?><div class="login-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div style="background:var(--color-success); color:#fff; padding:12px; border-radius:10px; margin-bottom:20px; text-align:center;"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label class="form-label">Código (No editable)</label>
            <input type="text" class="form-input" value="<?= htmlspecialchars($p['codigop']) ?>" disabled>
        </div>
        <?php if ($p['image_path']): ?>
            <div style="text-align:center; margin-bottom:15px;">
                <img src="<?= APP_URL . '/' . $p['image_path'] ?>" style="max-width:150px; border-radius:10px; border:1px solid var(--color-border);">
            </div>
        <?php endif; ?>
        <div class="form-group">
            <label class="form-label">Referencia</label>
            <input type="text" name="referencia" class="form-input" required value="<?= htmlspecialchars($p['referencia']) ?>">
        </div>
        <div class="stats-grid" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
                <label class="form-label">Existencia</label>
                <input type="number" step="1" name="exisact" class="form-input" value="<?= $p['exisact'] ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Precio (USD)</label>
                <input type="number" step="0.01" name="pventa" class="form-input" value="<?= $p['pventa'] ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Imagen (Opcional)</label>
            <input type="file" name="image" class="form-input" accept="image/*">
        </div>
        <button type="submit" class="btn btn-primary btn-lg mt-2">Guardar Cambios</button>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
