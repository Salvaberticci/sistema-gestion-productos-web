<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$db = getDB();
$rate = getExchangeRate();

// Filtros de fecha (por defecto hoy)
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-d');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

// 1. Resumen Financiero (Solo completados en el rango)
$stmt_resumen = $db->prepare("
    SELECT 
        COUNT(*) as total_tickets,
        COALESCE(SUM(total_usd), 0) as ingresos_usd,
        COALESCE(SUM(total_bs), 0) as ingresos_bs,
        (SELECT COUNT(*) FROM ordenes_venta WHERE estado = 'rechazado' AND DATE(created_at) BETWEEN ? AND ?) as total_rechazados
    FROM ordenes_venta 
    WHERE estado = 'completado' AND DATE(created_at) BETWEEN ? AND ?
");
$stmt_resumen->execute([$fecha_desde, $fecha_hasta, $fecha_desde, $fecha_hasta]);
$resumen = $stmt_resumen->fetch();

// 2. Ranking de Productos (Más vendidos)
$stmt_top_prod = $db->prepare("
    SELECT p.referencia, SUM(od.cantidad) as total_vendido, SUM(od.subtotal_usd) as total_dinero
    FROM ordenes_detalles od
    JOIN productos p ON od.producto_cod = p.codigop
    JOIN ordenes_venta ov ON od.orden_id = ov.id
    WHERE ov.estado = 'completado' AND DATE(ov.created_at) BETWEEN ? AND ?
    GROUP BY od.producto_cod
    ORDER BY total_vendido DESC
    LIMIT 5
");
$stmt_top_prod->execute([$fecha_desde, $fecha_hasta]);
$top_productos = $stmt_top_prod->fetchAll();

// 3. Ranking de Clientes (Mayores compradores)
$stmt_top_cli = $db->prepare("
    SELECT c.nombre, c.apellido, COUNT(ov.id) as compras_realizadas, SUM(ov.total_usd) as total_generado
    FROM ordenes_venta ov
    JOIN clientes c ON ov.cliente_id = c.id
    WHERE ov.estado = 'completado' AND DATE(ov.created_at) BETWEEN ? AND ?
    GROUP BY ov.cliente_id
    ORDER BY total_generado DESC
    LIMIT 5
");
$stmt_top_cli->execute([$fecha_desde, $fecha_hasta]);
$top_clientes = $stmt_top_cli->fetchAll();

// 4. Historial Completo
$stmt_historial = $db->prepare("
    SELECT ov.*, c.nombre, c.apellido, u.nombre_completo as vendedor
    FROM ordenes_venta ov
    JOIN clientes c ON ov.cliente_id = c.id
    JOIN usuarios u ON ov.usuario_id = u.id
    WHERE DATE(ov.created_at) BETWEEN ? AND ?
    ORDER BY ov.created_at DESC
");
$stmt_historial->execute([$fecha_desde, $fecha_hasta]);
$historial = $stmt_historial->fetchAll();

$pageTitle = 'Reportes y Cierres';
$currentModule = 'reportes';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<!-- Overlay de Carga -->
<div id="loading-overlay" style="display:none; position:fixed; inset:0; background:rgba(11,14,20,0.8); z-index:9999; backdrop-filter:blur(5px); display:none; align-items:center; justify-content:center; flex-direction:column; gap:15px;">
    <div class="spinner"></div>
    <div style="color:var(--color-accent); font-weight:700; letter-spacing:1px; animation: pulse 1.5s infinite;">GENERANDO REPORTE...</div>
</div>

<style>
    @keyframes pulse { 0%, 100% { opacity:1; } 50% { opacity:0.5; } }
    .spinner {
        width: 50px;
        height: 50px;
        border: 4px solid rgba(88,166,255,0.1);
        border-left-color: var(--color-accent);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>

<div class="page-header">
    <h2 class="page-title">📊 Reportes y Cierres</h2>
    <div class="flex items-center gap-2">
        <span class="text-dim">Tasa: <?= formatCurrency($rate, 'Bs.') ?></span>
    </div>
</div>

<!-- Filtros de Fecha -->
<div class="card mb-4 report-section">
    <form method="GET" class="report-filter-form">
        <div class="filter-group">
            <div class="form-group">
                <label class="form-label">Desde</label>
                <input type="date" name="fecha_desde" class="form-input" value="<?= $fecha_desde ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Hasta</label>
                <input type="date" name="fecha_hasta" class="form-input" value="<?= $fecha_hasta ?>">
            </div>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary" onclick="showLoading()">📊 Ver Reporte</button>
            <a href="index.php" class="btn btn-secondary" onclick="showLoading()">🔄 Hoy</a>
            <button type="button" class="btn btn-gold" onclick="exportarPDF()" title="Exportar este periodo a PDF">
                📄 Exportar PDF
            </button>
        </div>
    </form>
</div>

<style>
    .report-filter-form { display: flex; flex-direction: column; gap: 15px; }
    .filter-group { display: flex; gap: 15px; flex-wrap: wrap; }
    .filter-group .form-group { flex: 1; min-width: 140px; margin-bottom: 0; }
    .filter-actions { display: flex; gap: 10px; }
    .filter-actions .btn { flex: 1; height: 52px; font-size: 0.95rem; }

    @media (min-width: 768px) {
        .report-filter-form { flex-direction: row; align-items: flex-end; }
        .filter-group { flex: 1; }
        .filter-actions { flex-shrink: 0; }
        .filter-actions .btn { width: auto; flex: none; }
    }
    
    @media (max-width: 480px) {
        .filter-actions { flex-direction: column; }
    }
</style>

<!-- Resumen de Corto/Cierre -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-label">Tickets Exitosos</div>
        <div class="stat-value text-success"><?= $resumen['total_tickets'] ?></div>
        <div class="stat-sub">Ventas aprobadas</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">❌</div>
        <div class="stat-label">Tickets Rechazados</div>
        <div class="stat-value text-danger"><?= $resumen['total_rechazados'] ?></div>
        <div class="stat-sub">Sin efecto en stock</div>
    </div>
    <div class="stat-card" style="border-bottom: 3px solid var(--color-gold);">
        <div class="stat-icon">💵</div>
        <div class="stat-label">Total Ingresos USD</div>
        <div class="stat-value text-gold"><?= formatCurrency((float)$resumen['ingresos_usd']) ?></div>
    </div>
    <div class="stat-card" style="border-bottom: 3px solid var(--color-accent);">
        <div class="stat-icon">🇻🇪</div>
        <div class="stat-label">Total Ingresos Bs.</div>
        <div class="stat-value text-accent"><?= formatCurrency((float)$resumen['ingresos_bs'], 'Bs.') ?></div>
    </div>
</div>

<style>
    .report-section { padding: 15px; }
    .data-table th, .data-table td { padding: 12px 15px; }
    
    /* Mejoras de flujo en móvil */
    @media (max-width: 767px) {
        .desktop-only { display: none !important; }
        .mobile-only { display: block !important; }
        .stats-grid { gap: 10px; }
        .stat-card { padding: 15px 10px; }
        .report-section { padding: 10px; }
        .page-title { font-size: 1.35rem; }
        .card-header { padding: 12px 0; }
        .product-card { padding: 15px !important; flex-direction: column; align-items: stretch !important; }
        .product-card-title { font-size: 0.875rem !important; word-break: break-word; }
    }
    @media (min-width: 768px) {
        .mobile-only { display: none !important; }
    }
</style>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    <!-- Top Productos -->
    <div class="card p-0">
        <div class="card-header">
            <h3 class="card-title" style="padding-left:15px;">🏆 Top Productos (Más Vendidos)</h3>
        </div>
        <div class="report-section" style="padding-top:0;">
            <!-- Vista Desktop -->
            <table class="data-table desktop-only">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th style="text-align:center;">Cant.</th>
                        <th style="text-align:right;">Ingreso</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($top_productos)): ?>
                        <tr><td colspan="3" class="text-center text-dim">Sin datos en este rango</td></tr>
                    <?php else: foreach($top_productos as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['referencia']) ?></td>
                            <td style="text-align:center;"><span class="badge-active" style="background:#30363d;"><?= (int)$p['total_vendido'] ?></span></td>
                            <td style="text-align:right;" class="text-accent fw-bold"><?= formatCurrency((float)$p['total_dinero']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <!-- Vista Móvil -->
            <div class="mobile-only">
                <?php if(empty($top_productos)): ?>
                    <div class="text-center text-dim p-2">Sin datos</div>
                <?php else: foreach($top_productos as $p): ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid var(--color-border);">
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:700; font-size:0.85rem; word-break:break-word;"><?= htmlspecialchars($p['referencia']) ?></div>
                            <div style="font-size:0.7rem; color:var(--color-text-dim);">Ventas: <?= (int)$p['total_vendido'] ?></div>
                        </div>
                        <div style="font-weight:800; color:var(--color-accent); font-size:0.9rem; margin-left:10px;">
                            <?= formatCurrency((float)$p['total_dinero']) ?>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Top Clientes -->
    <div class="card p-0">
        <div class="card-header">
            <h3 class="card-title" style="padding-left:15px;">👤 Top Clientes Frecuentes</h3>
        </div>
        <div class="report-section" style="padding-top:0;">
            <!-- Vista Desktop -->
            <table class="data-table desktop-only">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th style="text-align:center;">Tickets</th>
                        <th style="text-align:right;">Total Spent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($top_clientes)): ?>
                        <tr><td colspan="3" class="text-center text-dim">Sin datos en este rango</td></tr>
                    <?php else: foreach($top_clientes as $c): ?>
                        <tr>
                            <td><span class="fw-bold"><?= htmlspecialchars($c['nombre'].' '.$c['apellido']) ?></span></td>
                            <td style="text-align:center;"><span class="badge-active" style="background:#30363d;"><?= $c['compras_realizadas'] ?></span></td>
                            <td style="text-align:right;" class="text-gold fw-bold"><?= formatCurrency((float)$c['total_generado']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

            <!-- Vista Móvil -->
            <div class="mobile-only">
                <?php if(empty($top_clientes)): ?>
                    <div class="text-center text-dim p-2">Sin datos</div>
                <?php else: foreach($top_clientes as $c): ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid var(--color-border);">
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:700; font-size:0.85rem; word-break:break-word;"><?= strtoupper(htmlspecialchars($c['nombre'].' '.$c['apellido'])) ?></div>
                            <div style="font-size:0.7rem; color:var(--color-text-dim);">Tickets: <?= $c['compras_realizadas'] ?></div>
                        </div>
                        <div style="font-weight:800; color:var(--color-gold); font-size:0.9rem; margin-left:10px;">
                            <?= formatCurrency((float)$c['total_generado']) ?>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Historial de Órdenes -->
<div class="card p-0">
    <div class="card-header">
        <h3 class="card-title" style="padding-left:15px;">📜 Historial Detallado de Órdenes</h3>
    </div>
    
    <!-- Vista Desktop -->
    <div class="table-responsive desktop-only" style="padding: 0 20px 20px 20px;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Estado</th>
                    <th>Cliente</th>
                    <th>Vendedor</th>
                    <th>Monto</th>
                    <th>Fecha</th>
                    <th style="text-align:right;">Ticket</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($historial)): ?>
                    <tr><td colspan="7" class="text-center p-4 text-dim">No se encontraron órdenes para este período.</td></tr>
                <?php else: foreach($historial as $h): ?>
                    <tr>
                        <td><span class="text-dim">#</span><?= $h['id'] ?></td>
                        <td>
                            <?php if ($h['estado'] === 'completado'): ?>
                                <span class="badge-active" style="background:var(--color-success); color:#fff; font-size:0.7rem;">Aprobado</span>
                            <?php elseif ($h['estado'] === 'rechazado'): ?>
                                <span class="badge-dim" style="background:var(--color-danger); color:#fff; font-size:0.7rem;">Rechazado</span>
                            <?php else: ?>
                                <span class="badge-dim" style="background:var(--color-gold); color:#111; font-size:0.7rem;">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="fw-bold"><?= htmlspecialchars($h['nombre'].' '.$h['apellido']) ?></span></td>
                        <td><small class="text-dim"><?= htmlspecialchars($h['vendedor']) ?></small></td>
                        <td class="fw-bold text-accent"><?= formatCurrency((float)$h['total_usd']) ?></td>
                        <td><small><?= date('d/m/y H:i', strtotime($h['created_at'])) ?></small></td>
                        <td style="text-align:right;">
                            <a href="ticket.php?id=<?= $h['id'] ?>" target="_blank" class="btn btn-secondary btn-sm" style="padding: 5px 10px;">
                                📄 PDF
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Vista Móvil (Tarjetas de Alta Densidad) -->
    <div class="mobile-only report-section" style="padding-top:0;">
        <?php if(empty($historial)): ?>
            <div class="text-center p-4 text-dim">No hay órdenes.</div>
        <?php else: foreach($historial as $h): ?>
            <div class="product-card" style="margin-bottom:15px; border:1px solid var(--color-border); border-radius:12px; background: rgba(255,255,255,0.02); overflow:hidden;">
                <!-- Cabecera de Tarjeta -->
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <span style="font-size:0.7rem; font-weight:800; color:var(--color-text-dim);">ORDEN #<?= $h['id'] ?></span>
                    <?php if ($h['estado'] === 'completado'): ?>
                        <span style="background:var(--color-success); color:#fff; padding:2px 8px; font-size:0.6rem; border-radius:4px; font-weight:bold;">APROBADO</span>
                    <?php else: ?>
                        <span style="background:var(--color-danger); color:#fff; padding:2px 8px; font-size:0.6rem; border-radius:4px; font-weight:bold;">RECHAZADO</span>
                    <?php endif; ?>
                </div>

                <!-- Info de Cliente -->
                <div class="product-card-title" style="font-weight:800; line-height:1.2; font-size:1rem; margin-bottom:5px;">
                    <?= strtoupper(htmlspecialchars($h['nombre'].' '.$h['apellido'])) ?>
                </div>
                
                <div style="font-size:0.75rem; color:var(--color-text-dim);">
                    Vendedor: <b><?= htmlspecialchars($h['vendedor']) ?></b>
                </div>
                <div style="font-size:0.7rem; color:var(--color-text-dim); margin-top:2px;">
                    Fecha: <?= date('d/m/y H:i', strtotime($h['created_at'])) ?>
                </div>
                
                <!-- Área de Total y Acción (Vertical en Móvil) -->
                <div style="margin-top:15px; background: rgba(0,0,0,0.2); padding: 12px; border-radius:8px; border: 1px solid rgba(88,166,255,0.1);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                        <span style="font-size:0.75rem; color:var(--color-text-dim);">Monto Total:</span>
                        <span style="font-size:1.15rem; font-weight:800; color:var(--color-accent);"><?= formatCurrency((float)$h['total_usd']) ?></span>
                    </div>
                    <a href="ticket.php?id=<?= $h['id'] ?>" target="_blank" class="btn btn-primary" style="width:100%; height:42px; font-size:0.875rem;">
                        📄 Ver Ticket PDF
                    </a>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<script>
function showLoading() {
    const overlay = document.getElementById('loading-overlay');
    overlay.style.display = 'flex';
}

function exportarPDF() {
    const desde = "<?= $fecha_desde ?>";
    const hasta = "<?= $fecha_hasta ?>";
    
    showLoading();
    
    // Abrir en nueva pestaña
    window.open(`exportar_pdf.php?fecha_desde=${desde}&fecha_hasta=${hasta}`, '_blank');
    
    // Ocultar loading después de un momento (la descarga ocurre por separado)
    setTimeout(() => {
        document.getElementById('loading-overlay').style.display = 'none';
    }, 2000);
}

// Ocultar loading si la página se carga de nuevo (cache/back)
window.onpageshow = function(event) {
    if (event.persisted) {
        document.getElementById('loading-overlay').style.display = 'none';
    }
};
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
