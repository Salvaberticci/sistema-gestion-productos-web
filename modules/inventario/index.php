<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Inventario de Productos';
$currentModule = 'inventario';
$extraScripts = [APP_URL . '/assets/js/search.js'];
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-header">
    <h2 class="page-title">📦 Inventario</h2>
    <a href="crear.php" class="btn btn-primary btn-sm">
        <span>➕</span> Nuevo Producto
    </a>
</div>

<!-- Search Bar -->
<div class="search-bar">
    <input type="text" id="inventorySearch" class="form-input" placeholder="Buscar por nombre o código..." autocomplete="off">
</div>

<!-- Results Area -->
<div class="card p-0" id="inventoryContainer">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Referencia</th>
                    <th>Código</th>
                    <th>Stock</th>
                    <th>Precio (USD/Bs.)</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody id="desktopInventoryBody">
                <!-- AJAX Load -->
            </tbody>
        </table>
    </div>

    <!-- Mobile View -->
    <div class="product-cards p-2" id="mobileProductCards">
        <!-- AJAX Load -->
    </div>
</div>

<script>
    window.APP_URL = '<?= APP_URL ?>';
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
