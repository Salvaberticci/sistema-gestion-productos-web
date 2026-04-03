<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

// Forzar que el navegador no cachee esta página para ver cambios en tiempo real
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$db = getDB();
$totalProducts = $db->query("SELECT COUNT(*) FROM productos")->fetchColumn();
$totalStock = $db->query("SELECT COALESCE(SUM(exisact), 0) FROM productos")->fetchColumn();
$totalImages = $db->query("SELECT COUNT(*) FROM productos WHERE image_path IS NOT NULL AND image_path != ''")->fetchColumn();
$rate = getExchangeRate();

$mostStock = $db->query("SELECT referencia, exisact FROM productos ORDER BY exisact DESC LIMIT 1")->fetch();
$leastStock = $db->query("SELECT referencia, exisact FROM productos WHERE exisact > 0 ORDER BY exisact ASC LIMIT 1")->fetch();
$highestPrice = $db->query("SELECT referencia, pventa FROM productos ORDER BY pventa DESC LIMIT 1")->fetch();
$lowestPrice = $db->query("SELECT referencia, pventa FROM productos WHERE pventa > 0 ORDER BY pventa ASC LIMIT 1")->fetch();
$topSearched = $db->query("SELECT referencia, busquedas FROM productos WHERE busquedas > 0 ORDER BY busquedas DESC LIMIT 5")->fetchAll();

$pendingOrders = [];
if (isAdmin()) {
    $pendingOrders = $db->query("
        SELECT ov.*, c.nombre, c.apellido 
        FROM ordenes_venta ov 
        JOIN clientes c ON ov.cliente_id = c.id 
        WHERE ov.estado = 'pendiente' 
        ORDER BY ov.created_at ASC
    ")->fetchAll();
}

$pageTitle = 'Dashboard';
$currentModule = 'dashboard';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-header">
    <h2 class="page-title">Panel Principal</h2>
    <div style="text-align:right;">
        <span class="text-dim" style="font-size:0.8125rem;">Tasa: <?= formatCurrency($rate, 'Bs.') ?></span><br>
        <small class="text-dim" style="font-size:0.65rem;">Actualizado: <?= date('d/m/Y H:i:s') ?></small>
    </div>
</div>

<div id="pendingOrdersContainer">
<?php if (isAdmin() && !empty($pendingOrders)): ?>
    <!-- Ventas por Aprobar -->
    <div class="card p-0 mb-4" style="border-top: 4px solid var(--color-danger);">
        <div class="card-header flex justify-between items-center" style="padding: 15px;">
            <h3 class="card-title">🚨 Ventas por Aprobar</h3>
            <span class="badge-admin" style="font-size: 0.75rem;"><?= count($pendingOrders) ?> PENDIENTE<?= count($pendingOrders) !== 1 ? 'S' : '' ?></span>
        </div>

        <!-- Vista Móvil (Tarjetas) -->
        <div class="product-cards p-3" style="display: flex; border-top: 1px solid var(--color-border); flex-direction: column; gap: 10px;">
            <?php foreach ($pendingOrders as $o): ?>
            <div class="product-card" onclick="viewOrder(<?= $o['id'] ?>)" style="width: 100%; cursor: pointer; margin: 0;">
                <div class="product-card-info">
                    <div class="product-card-name" style="font-size: 1rem;">
                        <span class="text-dim">#<?= $o['id'] ?></span> <?= htmlspecialchars($o['nombre'] . ' ' . $o['apellido']) ?>
                    </div>
                    <div class="product-card-code"><?= date('d/m/y H:i', strtotime($o['created_at'])) ?></div>
                </div>
                <div class="product-card-price" style="display:flex; flex-direction:column; align-items:flex-end; gap:2px;">
                    <span><?= formatCurrency($o['total_usd']) ?></span>
                    <small style="font-size:0.7rem; color:var(--color-gold);"><?= formatCurrency((float)$o['total_bs'], 'Bs.') ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Vista Desktop (Tabla) -->
        <div class="table-responsive desktop-only">
            <table class="data-table" style="display: table;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Monto</th>
                        <th>Fecha</th>
                        <th style="text-align:right;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingOrders as $o): ?>
                    <tr>
                        <td><span class="text-dim">#</span><?= $o['id'] ?></td>
                        <td><span class="fw-bold"><?= htmlspecialchars($o['nombre'] . ' ' . $o['apellido']) ?></span></td>
                        <td>
                            <div class="text-accent fw-bold"><?= formatCurrency($o['total_usd']) ?></div>
                            <div class="text-dim" style="font-size:0.7rem;"><?= formatCurrency((float)$o['total_bs'], 'Bs.') ?></div>
                        </td>
                        <td><?= date('d/m/y H:i', strtotime($o['created_at'])) ?></td>
                        <td style="text-align:right;">
                            <button onclick="viewOrder(<?= $o['id'] ?>)" class="btn btn-primary btn-sm">👁️ Detalle</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <style>
        @media (max-width: 767px) {
            .desktop-only { display: none !important; }
        }
        @media (min-width: 768px) {
            .product-cards { display: none !important; }
        }
    </style>
<?php endif; ?>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div class="stat-label">Productos</div>
        <div class="stat-value text-accent"><?= number_format($totalProducts) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-label">Stock Total</div>
        <div class="stat-value text-gold"><?= number_format($totalStock, 0) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📈</div>
        <div class="stat-label">Mayor Stock</div>
        <div class="stat-value text-success"><?= $mostStock ? number_format($mostStock['exisact'], 0) : '0' ?></div>
        <div class="stat-sub"><?= htmlspecialchars($mostStock['referencia'] ?? 'N/A') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📉</div>
        <div class="stat-label">Menor Stock</div>
        <div class="stat-value text-danger"><?= $leastStock ? number_format($leastStock['exisact'], 0) : '0' ?></div>
        <div class="stat-sub"><?= htmlspecialchars($leastStock['referencia'] ?? 'N/A') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🖼️</div>
        <div class="stat-label">Con Imagen</div>
        <div class="stat-value text-accent"><?= number_format($totalImages) ?></div>
        <div class="stat-sub">Productos cargados</div>
    </div>
</div>

<!-- Price Cards -->
<div class="stats-grid" style="grid-template-columns: 1fr 1fr;">
    <div class="stat-card">
        <div class="stat-icon">💎</div>
        <div class="stat-label">Producto Más Caro</div>
        <div class="stat-value text-gold"><?= $highestPrice ? formatCurrency($highestPrice['pventa']) : 'N/A' ?></div>
        <div class="stat-sub"><?= htmlspecialchars($highestPrice['referencia'] ?? 'Sin datos') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🏷️</div>
        <div class="stat-label">Producto Más Barato</div>
        <div class="stat-value text-success"><?= $lowestPrice ? formatCurrency($lowestPrice['pventa']) : 'N/A' ?></div>
        <div class="stat-sub"><?= htmlspecialchars($lowestPrice['referencia'] ?? 'Sin datos') ?></div>
    </div>
</div>

<!-- Top Searched -->
<?php if (!empty($topSearched)): ?>
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">🔥 Top Buscados</h3>
    </div>
    <?php foreach ($topSearched as $i => $p): ?>
    <div style="display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid var(--color-border);">
        <span style="font-size:1.125rem; font-weight:800; color:var(--color-gold); width:30px;">#<?= $i + 1 ?></span>
        <div style="flex:1; min-width:0;">
            <div style="font-size:0.875rem; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($p['referencia']) ?></div>
            <div style="font-size:0.75rem; color:var(--color-text-dim);"><?= $p['busquedas'] ?> consultas</div>
        </div>
        <span style="font-size:0.6875rem; font-weight:700; background:var(--color-success); color:#fff; padding:4px 10px; border-radius:20px;">POPULAR</span>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="card mt-3">
    <div class="empty-state">
        <div class="empty-state-icon">📊</div>
        <p class="empty-state-text">Aún no hay búsquedas registradas</p>
    </div>
</div>
<?php endif; ?>

<script>
    window.APP_URL = '<?= APP_URL ?>';

function viewOrder(orderId) {
    Swal.fire({
        title: 'Cargando orden...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
            fetch(`${window.APP_URL}/modules/dashboard/api_orden_detalle.php?id=${orderId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        let html = `
                            <div style="text-align:left;">
                                <p><b>Cliente:</b> ${data.orden.nombre} ${data.orden.apellido}</p>
                                <p><b>Fecha:</b> ${data.orden.fecha}</p>
                                <hr style="border-color:var(--color-border); margin: 15px 0;">
                                <table style="width:100%; font-size:0.85rem; border-collapse:collapse;">
                                    <thead>
                                        <tr style="border-bottom:1px solid var(--color-border);">
                                            <th style="text-align:left; padding:8px 0;">Producto</th>
                                            <th style="text-align:center;">Cant</th>
                                            <th style="text-align:right;">Precio</th>
                                            <th style="text-align:right;">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.items.map(i => `
                                            <tr style="border-bottom:1px solid var(--color-border);">
                                                <td style="padding:10px 0;">${i.referencia}<br><small class="text-dim">#${i.producto_cod}</small></td>
                                                <td style="text-align:center;">${i.cantidad}</td>
                                                <td style="text-align:right;">$${parseFloat(i.precio_unitario_usd).toFixed(2)}</td>
                                                <td style="text-align:right; font-weight:bold;">$${parseFloat(i.subtotal_usd).toFixed(2)}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                                <div style="margin-top:20px; text-align:right;">
                                    <div style="font-size:1.4rem; font-weight:900; color:var(--color-success);">$${parseFloat(data.orden.total_usd).toFixed(2)}</div>
                                    <div style="font-size:1rem; font-weight:700; color:var(--color-gold);">Bs. ${parseFloat(data.orden.total_bs).toFixed(2)}</div>
                                </div>
                            </div>
                        `;

                        Swal.fire({
                            title: `Orden #` + orderId,
                            html: html,
                            width: '600px',
                            showCancelButton: true,
                            showDenyButton: true,
                            confirmButtonText: '✅ Aprobar Venta',
                            denyButtonText: '❌ Rechazar',
                            cancelButtonText: 'Cerrar',
                            confirmButtonColor: 'var(--color-success)',
                            denyButtonColor: 'var(--color-danger)',
                            background: '#0D1117',
                            color: '#ffffff'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                processOrder(orderId, 'aprobar');
                            } else if (result.isDenied) {
                                processOrder(orderId, 'rechazar');
                            }
                        });
                    } else {
                        Swal.fire('Error', data.error, 'error');
                    }
                });
        }
    });
}

function processOrder(orderId, action) {
    Swal.fire({
        title: 'Procesando...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
            fetch(`${window.APP_URL}/modules/dashboard/api_orden_accion.php`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: orderId, accion: action})
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: action === 'aprobar' ? '¡Venta Aprobada!' : 'Orden Rechazada',
                        text: data.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.error, 'error');
                }
            });
        }
    });
}

// Real-time Dashboard Polling (Every 5 seconds)
let lastOrderCount = <?= count($pendingOrders) ?>;

function refreshPendingOrders() {
    fetch(`${window.APP_URL}/modules/dashboard/api_ordenes_fragmento.php`)
        .then(res => res.text())
        .then(html => {
            const container = document.getElementById('pendingOrdersContainer');
            if (html.trim() === "") {
                container.innerHTML = "";
                lastOrderCount = 0;
            } else {
                container.innerHTML = html;
                // Opcional: Podríamos contar las órdenes en el HTML recibido para saber si hay nuevas
            }
        })
        .catch(err => console.error('Error al refrescar órdenes:', err));
}

setInterval(refreshPendingOrders, 5000);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
