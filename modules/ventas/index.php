<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$pageTitle = 'Generar Orden de Venta';
$currentModule = 'ventas';

// Cargar JS específicos
$extraScripts = [
    APP_URL . '/assets/vendor/html5-qrcode.min.js',
    APP_URL . '/assets/js/pos.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<!-- Estilos específicos del POS -->
<style>
.pos-layout {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: flex-start;
}
.pos-left { flex: 1; min-width: 300px; display: flex; flex-direction: column; gap: 15px; }
.pos-right { width: 350px; position: sticky; top: 80px; }

@media (max-width: 768px) {
    .pos-right { width: 100%; position: static; }
    .pos-layout { flex-direction: column; }
}

.cart-item {
    display: flex; justify-content: space-between; align-items: center;
    background: #161b22; padding: 15px; border-radius: 12px; margin-bottom: 12px; border: 1px solid var(--color-border);
    transition: transform 0.2s;
}
.cart-item:hover { transform: translateX(2px); border-color: var(--color-accent); }
.cart-item-name { font-size: 0.9rem; font-weight: 600; margin-bottom: 4px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; line-clamp:2; -webkit-box-orient: vertical; color: #fff; }
.cart-item-price { font-size: 1rem; color: var(--color-accent); font-weight: 800; }
.cart-item-qty { background: #30363d; padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: 800; color: var(--color-gold); }

#posSearch {
    height: 55px;
    font-size: 1.15rem;
    padding-left: 20px;
    border-radius: 12px;
    border: 2px solid var(--color-border);
    background: #0d1117;
}
#posSearch:focus { border-color: var(--color-accent); box-shadow: 0 0 0 4px rgba(88, 166, 255, 0.15); }
</style>

<div class="page-header" style="margin-bottom: 15px;">
    <div>
        <h2 class="page-title">🛒 Generar Orden de Venta</h2>
        <span style="color:var(--color-text-dim); font-size:0.9rem;">Área de Cajero Fast-Track</span>
    </div>
    <button class="btn btn-secondary btn-sm" onclick="resetVenta()">Limpiar Formulario</button>
</div>

<div class="pos-layout">
    <!-- PANEL IZQUIERDO: Formulario de Cliente + Búsqueda y Escáner -->
    <div class="pos-left">
        
        <!-- CARD DE CLIENTE -->
        <div class="card" style="padding: 15px; border-top: 3px solid var(--color-accent);">
            <h3 style="margin-bottom: 15px; font-size:1.1rem; border-bottom: 1px solid var(--color-border); padding-bottom: 10px;">🧑‍💼 Datos del Cliente</h3>
            
            <div class="stats-grid" style="grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label text-accent">Buscar o Crear por Cédula</label>
                    <div style="display:flex; gap:10px;">
                        <input type="text" id="c_cedula" class="form-input" placeholder="Ej: V-12345678" tabindex="1">
                        <button type="button" class="btn btn-secondary" onclick="buscarCliente()" style="padding: 0 15px;">🔍</button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <input type="text" id="c_nombre" class="form-input" required tabindex="2">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Apellido</label>
                    <input type="text" id="c_apellido" class="form-input" required tabindex="3">
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1; margin-bottom:0;">
                    <label class="form-label">Teléfono / Celular</label>
                    <input type="text" id="c_telefono" class="form-input" required tabindex="4">
                </div>
            </div>
        </div>

        <!-- BUSCADOR DE PRODUCTOS -->
        <div class="card" style="padding: 15px; border-top: 3px solid var(--color-gold);">
             <h3 style="margin-bottom: 15px; font-size:1.1rem; border-bottom: 1px solid var(--color-border); padding-bottom: 10px;">🔍 Búsqueda de Productos</h3>
             <p style="font-size: 0.75rem; color: var(--color-text-dim); margin-bottom: 12px;">Escriba el nombre, el código o use la cámara para escanear.</p>
             <div style="display:flex; gap:10px; align-items: center;">
                <div style="flex:1; position:relative;">
                    <input type="text" id="posSearch" class="form-input" placeholder="Referencia o Escaneo..." autocomplete="off">
                </div>
                <button id="startScannerBtn" class="btn btn-primary" style="height: 55px; width: 60px; font-size: 1.3rem;">📸</button>
            </div>
        </div>

        <!-- AREA DE ESCANER OCULTA -->
        <div class="card p-0" id="scannerDiv" style="display:none; width: 100%; padding:15px; text-align:center;">
             <div class="scanner-wrap" style="height:250px; margin-bottom:15px;">
                <div class="scanner-container" id="reader-container" style="height:100%;">
                    <div id="reader"></div>
                    <div class="scanner-overlay">
                        <div class="scanner-outline"><div class="scanner-line"></div></div>
                    </div>
                </div>
            </div>
            <button id="stopScannerBtn" class="btn btn-danger btn-sm">Detener Cámara</button>
        </div>

        <!-- RESULTADOS DE BUSQUEDA -->
        <div id="searchResults" style="display:flex; flex-direction:column; gap:10px;">
            <div class="text-center p-3 text-dim">Busque un producto para añadir al carrito</div>
        </div>
    </div>

    <!-- PANEL DERECHO: Carrito / Ticket -->
    <div class="pos-right">
        <div class="card p-0" style="padding: 0; overflow: hidden; border-top: 3px solid var(--color-success);">
            <h3 style="margin: 15px; font-size:1.1rem; border-bottom: 1px solid var(--color-border); padding-bottom: 15px;">🧾 Ticket Virtual</h3>
            
            <div id="cartItemsContainer" style="min-height: 200px; max-height: 450px; overflow-y:auto; padding: 0 15px; margin-bottom: 15px;">
                <div class="text-center text-dim mt-3">El carrito está vacío</div>
            </div>

            <div style="border-top: 1px solid var(--color-border); padding: 20px; background: rgba(0,0,0,0.2);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                    <span style="color:var(--color-text-dim); font-weight: 800; font-size: 1.1rem;">TOTAL A PAGAR:</span>
                    <span id="cartTotalBS" style="font-size:2.2rem; font-weight:900; color:var(--color-success);">Bs. 0.00</span>
                </div>
                <div id="cartTotalUSD" style="display:none;"></div>
                
                <button id="btnEnviarOrden" class="btn btn-success" style="width: 100%; height:60px; font-size:1.2rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;" onclick="preEnviarOrden()" disabled>
                    🚀 Enviar Orden de Venta
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.APP_URL = '<?= APP_URL ?>';
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
