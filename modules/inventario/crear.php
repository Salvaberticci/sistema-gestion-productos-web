<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cod = trim($_POST['codigop'] ?? '');
    $ref = trim($_POST['referencia'] ?? '');
    $stock = (float)($_POST['exisact'] ?? 0);
    $price = (float)($_POST['pventa'] ?? 0);
    
    if (empty($cod) || empty($ref)) {
        $error = 'Código y Referencia son obligatorios.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT codigop FROM productos WHERE codigop = ?");
        $stmt->execute([$cod]);
        if ($stmt->fetch()) {
            $error = 'El código de barras ya existe.';
        } else {
            // Manejo de Imagen
            $image_path = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = $cod . '_' . time() . '.' . $ext;
                $target = __DIR__ . '/../../assets/images/products/' . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    $image_path = 'assets/images/products/' . $filename;
                }
            }

            try {
                $stmt = $db->prepare("INSERT INTO productos (codigop, referencia, exisact, pventa, image_path) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$cod, $ref, $stock, $price, $image_path]);
                $success = 'Producto creado con éxito.';
            } catch (Exception $e) {
                $error = 'Error al guardar: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Nuevo Producto';
$currentModule = 'inventario';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-header">
    <h2 class="page-title">📦 Nuevo Producto</h2>
    <a href="index.php" class="btn btn-secondary btn-sm">← Volver</a>
</div>

<div class="card">
    <?php if ($error): ?>
        <div class="login-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div style="background:var(--color-success); color:#fff; padding:12px; border-radius:10px; margin-bottom:20px; text-align:center;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label class="form-label">Código de Barras / ID</label>
            <input type="text" name="codigop" class="form-input" placeholder="Escanea o escribe el código" required autofocus>
        </div>
        <div class="form-group">
            <label class="form-label">Referencia / Nombre</label>
            <input type="text" name="referencia" class="form-input" placeholder="Nombre descriptivo del producto" required>
        </div>
        <div class="stats-grid" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
                <label class="form-label">Existencia Actual</label>
                <input type="number" step="1" name="exisact" class="form-input" value="0">
            </div>
            <div class="form-group">
                <label class="form-label">Precio (USD)</label>
                <input type="number" step="0.01" name="pventa" class="form-input" value="0.00">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Imagen del Producto</label>
            <input type="file" name="image" class="form-input" accept="image/*" style="padding-top:8px;">
        </div>
        <button type="submit" class="btn btn-primary btn-lg mt-2">Guardar Producto</button>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
