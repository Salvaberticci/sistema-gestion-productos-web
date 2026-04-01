<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Consulta de Precios';
$currentModule = 'consulta';
$extraScripts = [APP_URL . '/assets/js/consulta.js'];
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-header text-center flex-col">
    <img src="<?= APP_URL ?>/assets/images/logo.png" style="width:120px; margin-bottom:15px;">
    <h2 class="page-title text-gold" style="font-size:1.75rem;">Consulta de Precios</h2>
</div>

<div class="search-bar mt-2">
    <input type="text" id="publicSearch" class="form-input" placeholder="Escanea el código para ver el precio..." style="height:60px; font-size:1.25rem;" autofocus autocomplete="off">
</div>

<div id="consultaResult" class="mt-3" style="min-height:300px;">
    <!-- El resultado aparecerá aquí en grande -->
    <div class="empty-state">
        <div class="empty-state-icon" style="font-size:4rem; animation: pulse 2s infinite;">🔍</div>
        <p class="empty-state-text" style="font-size:1.125rem;">Esperando escaneo de producto...</p>
    </div>
</div>

<style>
@keyframes pulse {
    0% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.8; }
    100% { transform: scale(1); opacity: 0.5; }
}

@keyframes slideInUp {
    from { transform: translateY(50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.result-card {
    background: var(--color-card);
    border: 3px solid var(--color-accent);
    border-radius: 20px;
    padding: 30px;
    text-align: center;
    animation: slideInUp 0.4s ease-out;
    box-shadow: 0 15px 40px rgba(0,0,0,0.6);
}

.result-img {
    width: 180px;
    height: 180px;
    object-fit: cover;
    border-radius: 20px;
    margin-bottom: 20px;
    box-shadow: 0 10px 20px rgba(0,0,0,0.3);
}

.result-name {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--color-text-main);
    margin-bottom: 15px;
}

.result-price-bs {
    font-size: 3rem;
    font-weight: 800;
    color: var(--color-gold);
    line-height:1;
}

.result-price-usd {
    font-size: 1.5rem;
    color: var(--color-text-dim);
    margin-top: 5px;
}
</style>

<script>window.APP_URL = '<?= APP_URL ?>';</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
