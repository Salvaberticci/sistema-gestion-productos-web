<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Escáner de Cámara';
$currentModule = 'scanner';
$extraScripts = [
    APP_URL . '/assets/vendor/html5-qrcode.min.js',
    APP_URL . '/assets/js/scanner.js'
];
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-header">
    <h2 class="page-title">📸 Escanear Código</h2>
    <div class="flex gap-1">
        <button id="startBtn" class="btn btn-primary btn-sm">Iniciar Cámara</button>
        <button id="stopBtn" class="btn btn-danger btn-sm" style="display:none;">Detener</button>
    </div>
</div>

<div class="scanner-wrap">
    <div class="scanner-container" id="reader-container">
        <div id="reader"></div>
        <div class="scanner-overlay">
            <div class="scanner-outline">
                <div class="scanner-line"></div>
            </div>
        </div>
    </div>
</div>

<div id="scannerResult" class="mt-3">
    <!-- Resultado del escaneo -->
</div>

<div class="card mt-3">
    <div style="display:flex; align-items:center; gap:12px; color:var(--color-text-dim);">
        <span style="font-size:1.5rem;">💡</span>
        <p style="font-size:0.8125rem;">Coloca el código de barras dentro del recuadro azul. El sistema lo detectará automáticamente y mostrará el precio.</p>
    </div>
</div>

<script>
    window.APP_URL = '<?= APP_URL ?>';
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
