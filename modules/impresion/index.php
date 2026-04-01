?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Impresión de Tickets';
$currentModule = 'impresion';
$extraScripts = [APP_URL . '/assets/js/print_search.js'];
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-header">
    <h2 class="page-title">🖨️ Generar Ticket</h2>
</div>

<!-- Search Bar -->
<div class="search-bar">
    <input type="text" id="printSearch" class="form-input" placeholder="Buscar producto para imprimir..." autocomplete="off">
</div>

<div class="card p-0">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Producto</th>
                    <th>Referencia</th>
                    <th style="text-align:right;">Acción</th>
                </tr>
            </thead>
            <tbody id="printInventoryBody">
                <!-- AJAX Load -->
            </tbody>
        </table>
    </div>
    
    <div class="product-cards p-2" id="mobilePrintCards">
        <!-- AJAX Load -->
    </div>
</div>

<script>
    window.APP_URL = '<?= APP_URL ?>';
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
