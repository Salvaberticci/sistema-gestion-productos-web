<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_rate = (float)($_POST['tasa_cambio'] ?? 1.0);
    if ($new_rate <= 0) {
        $error = 'La tasa debe ser mayor a cero.';
    } else {
        try {
            $stmt = $db->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'tasa_cambio'");
            $stmt->execute([$new_rate]);
            $success = 'Tasa de cambio actualizada con éxito.';
        } catch (Exception $e) {
            $error = 'Error al actualizar: ' . $e->getMessage();
        }
    }
}

$rate = getExchangeRate();
$pageTitle = 'Configuración del Sistema';
$currentModule = 'configuracion';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-header">
    <h2 class="page-title">⚙️ Configuración</h2>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">💰 Tasa de Cambio (USD → Bs.)</h3>
    </div>
    
    <?php if ($error): ?><div class="login-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div style="background:var(--color-success); color:#fff; padding:12px; border-radius:10px; margin-bottom:20px; text-align:center;"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label class="form-label">Tasa de Cambio Actual</label>
            <div class="flex items-center gap-2">
                <span style="font-size:1.5rem; color:var(--color-gold); font-weight:800;">1 USD =</span>
                <input type="number" step="0.01" name="tasa_cambio" class="form-input" style="flex:1; font-size:1.5rem; font-weight:800; text-align:center;" value="<?= $rate ?>" required>
                <span style="font-size:1.5rem; color:var(--color-text-dim); font-weight:800;">BS.</span>
            </div>
            <p class="text-dim mt-1" style="font-size:0.75rem;">Utilizada para todos los cálculos de precios en el sistema.</p>
        </div>
        <button type="submit" class="btn btn-primary btn-lg mt-2">Guardar Configuración</button>
    </form>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">ℹ️ Información del Sistema</h3>
    </div>
    <div class="flex flex-col gap-2">
        <div class="flex justify-between">
            <span class="text-dim">Nombre:</span>
            <span class="fw-bold"><?= APP_NAME ?></span>
        </div>
        <div class="flex justify-between">
            <span class="text-dim">Versión:</span>
            <span class="text-accent fw-bold"><?= APP_VERSION ?></span>
        </div>
        <div class="flex justify-between">
            <span class="text-dim">Ambiente:</span>
            <span class="text-success fw-bold">PRODUCCIÓN (PHP 8.2)</span>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
