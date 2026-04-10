<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$db = getDB();

// Filtro de fecha
$fecha_filtro = $_GET['fecha'] ?? null; // Por defecto null (General)
if (isset($_GET['hoy'])) { $fecha_filtro = date('Y-m-d'); }

// --- 1. PRODUCTIVIDAD DE PERSONAL ---
if (empty($fecha_filtro)) {
    $productividad = $db->query("
        SELECT 
            u.id, u.nombre_completo, u.username, u.rol, u.activo,
            (SELECT COUNT(*) FROM historial_busquedas hb WHERE hb.usuario_id = u.id) as consultas_hoy,
            (SELECT COUNT(*) FROM ordenes_venta ov WHERE ov.usuario_id = u.id) as ordenes_hoy
        FROM usuarios u
        ORDER BY ordenes_hoy DESC, consultas_hoy DESC
    ")->fetchAll();
} else {
    $stmt_prod = $db->prepare("
        SELECT 
            u.id, u.nombre_completo, u.username, u.rol, u.activo,
            (SELECT COUNT(*) FROM historial_busquedas hb WHERE hb.usuario_id = u.id AND DATE(hb.fecha_busqueda) = ?) as consultas_hoy,
            (SELECT COUNT(*) FROM ordenes_venta ov WHERE ov.usuario_id = u.id AND DATE(ov.created_at) = ?) as ordenes_hoy
        FROM usuarios u
        ORDER BY ordenes_hoy DESC, consultas_hoy DESC
    ");
    $stmt_prod->execute([$fecha_filtro, $fecha_filtro]);
    $productividad = $stmt_prod->fetchAll();
}

// --- 2. DATOS PARA GRÁFICAS (DINÁMICOS) ---

// Grafica 1: Top 5 Vendidos en el Periodo
if (empty($fecha_filtro)) {
    $topSales = $db->query("
        SELECT p.referencia, SUM(od.cantidad) as total 
        FROM ordenes_detalles od 
        JOIN productos p ON od.producto_cod = p.codigop 
        JOIN ordenes_venta ov ON od.orden_id = ov.id 
        WHERE ov.estado = 'completado'
        GROUP BY od.producto_cod 
        ORDER BY total DESC 
        LIMIT 5
    ")->fetchAll();
} else {
    $stmt_sales = $db->prepare("
        SELECT p.referencia, SUM(od.cantidad) as total 
        FROM ordenes_detalles od 
        JOIN productos p ON od.producto_cod = p.codigop 
        JOIN ordenes_venta ov ON od.orden_id = ov.id 
        WHERE ov.estado = 'completado' AND DATE(ov.created_at) = ?
        GROUP BY od.producto_cod 
        ORDER BY total DESC 
        LIMIT 5
    ");
    $stmt_sales->execute([$fecha_filtro]);
    $topSales = $stmt_sales->fetchAll();
}

// Grafica 2: Top 5 Buscados en el Periodo
if (empty($fecha_filtro)) {
    $topSearches = $db->query("
        SELECT 
            COALESCE(
                p.referencia, 
                (SELECT p2.referencia FROM productos p2 WHERE p2.codigop = hb.termino_busqueda LIMIT 1),
                hb.termino_busqueda 
            ) as label,
            COUNT(hb.id) as total 
        FROM historial_busquedas hb 
        LEFT JOIN productos p ON hb.producto_cod = p.codigop 
        WHERE hb.termino_busqueda != ''
        GROUP BY label 
        ORDER BY total DESC 
        LIMIT 5
    ")->fetchAll();
} else {
    $stmt_searches = $db->prepare("
        SELECT 
            COALESCE(
                p.referencia, 
                (SELECT p2.referencia FROM productos p2 WHERE p2.codigop = hb.termino_busqueda LIMIT 1),
                hb.termino_busqueda 
            ) as label,
            COUNT(hb.id) as total 
        FROM historial_busquedas hb 
        LEFT JOIN productos p ON hb.producto_cod = p.codigop 
        WHERE DATE(hb.fecha_busqueda) = ? AND hb.termino_busqueda != ''
        GROUP BY label 
        ORDER BY total DESC 
        LIMIT 5
    ");
    $stmt_searches->execute([$fecha_filtro]);
    $topSearches = $stmt_searches->fetchAll();
}

$pageTitle = 'Estadísticas Avanzadas';
$currentModule = 'estadisticas';

// Scripts y Estilos Extra (Solo Charts, eliminamos Flatpickr por simplicidad y estabilidad)
$extraStyles = [];
$extraScripts = [
    'https://cdn.jsdelivr.net/npm/chart.js'
];

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-header">
    <h2 class="page-title">📈 Estadísticas y Métricas</h2>
</div>

<!-- Filtro de Fecha -->
<div class="card mb-4">
    <form method="GET" class="flex flex-col md:flex-row gap-3 items-end">
        <div style="flex:1;">
            <label class="form-label" style="font-size:0.85rem; font-weight:700; color:var(--color-text-dim);">
                <?= empty($fecha_filtro) ? '🔍 Filtrar por Fecha' : '📅 Fecha Seleccionada' ?>
            </label>
            <input type="date" id="datePicker" name="fecha" class="form-input" value="<?= htmlspecialchars($fecha_filtro ?? '') ?>" max="<?= date('Y-m-d') ?>" style="color-scheme: dark;">
        </div>
        <div class="flex gap-2 w-full md:w-auto">
            <button type="submit" class="btn btn-primary" style="height: 48px; flex:1; min-width:100px; font-weight:800;">
                Filtrar
            </button>
            <button type="submit" name="hoy" value="1" class="btn btn-secondary" style="height: 48px; padding: 0 15px; font-weight:700;">
                Hoy
            </button>
            <a href="index.php" class="btn btn-secondary" style="height: 48px; padding: 0 15px; line-height: 48px; text-decoration: none; text-align: center; border-color: var(--color-accent); color: var(--color-accent);">
                📊 Ver Todo
            </a>
        </div>
    </form>
</div>

<!-- Grilla de Empleados (Productividad) -->
<h3 class="card-title mb-3 px-2">
    👥 <?= empty($fecha_filtro) ? 'Rendimiento Histórico General' : 'Productividad del Personal ('.date('d/m/Y', strtotime($fecha_filtro)).')' ?>
</h3>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-5">
    <?php if(empty($productividad)): ?>
        <div class="col-span-full card p-4 text-center text-dim">No se encontraron usuarios para mostrar.</div>
    <?php else: foreach ($productividad as $u): ?>
        <div class="card p-4 transition-all hover:scale-[1.02]" style="border-left: 4px solid <?= $u['rol'] === 'admin' ? 'var(--color-accent)' : 'var(--color-gold)' ?>;">
            <div class="flex items-center gap-4 mb-4">
                <div class="user-avatar" style="width:45px; height:45px; font-size:1.1rem; background:rgba(255,255,255,0.05); color:var(--color-text); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800;">
                    <?= strtoupper(substr($u['nombre_completo'], 0, 1)) ?>
                </div>
                <div class="flex-1">
                    <div class="text-white font-bold text-md leading-tight">
                        <?= htmlspecialchars($u['nombre_completo']) ?>
                    </div>
                    <div class="text-dim text-xs mt-1">
                        <span class="text-accent opacity-80">@</span><?= htmlspecialchars($u['username']) ?> 
                        <span class="mx-1">•</span> 
                        <span class="<?= $u['rol'] === 'admin' ? 'text-accent' : 'text-gold' ?> font-bold uppercase tracking-widest" style="font-size:0.65rem;">
                            <?= ucfirst($u['rol']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-1">
                <div style="background:rgba(0,0,0,0.2); padding:10px; border-radius:10px; border:1px solid rgba(88,166,255,0.1); text-align:center;">
                    <div style="font-size:0.65rem; color:var(--color-text-dim); text-transform:uppercase; font-weight:800; letter-spacing:0.5px; margin-bottom:5px;">Consultas</div>
                    <div style="color:var(--color-accent); font-weight:900; font-size:1.5rem; line-height:1;"><?= number_format($u['consultas_hoy']) ?></div>
                </div>
                <div style="background:rgba(0,0,0,0.2); padding:10px; border-radius:10px; border:1px solid rgba(210,166,84,0.1); text-align:center;">
                    <div style="font-size:0.65rem; color:var(--color-text-dim); text-transform:uppercase; font-weight:800; letter-spacing:0.5px; margin-bottom:5px;">Tickets</div>
                    <div style="color:var(--color-gold); font-weight:900; font-size:1.5rem; line-height:1;"><?= number_format($u['ordenes_hoy']) ?></div>
                </div>
            </div>
            <?php if (!$u['activo']): ?>
                <div class="mt-3 text-center text-danger" style="font-size:0.75rem; font-weight:800;">(USUARIO INACTIVO)</div>
            <?php endif; ?>
        </div>
    <?php endforeach; endif; ?>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="card">
        <h3 class="card-title mb-4">🏆 <?= empty($fecha_filtro) ? 'Top 5 Más Vendidos (Histórico)' : 'Top Ventas del Día' ?></h3>
        <?php if(empty($topSales)): ?>
            <div style="height:300px; display:flex; align-items:center; justify-content:center;" class="text-dim">No hay ventas registradas en esta fecha.</div>
        <?php else: ?>
            <canvas id="salesChart" style="max-height:300px;"></canvas>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3 class="card-title mb-4">🔥 <?= empty($fecha_filtro) ? 'Tendencias de Búsqueda (Global)' : 'Interés del Momento (Búsquedas)' ?></h3>
        <?php if(empty($topSearches)): ?>
            <div style="height:300px; display:flex; align-items:center; justify-content:center;" class="text-dim">No hay búsquedas registradas en esta fecha.</div>
        <?php else: ?>
            <canvas id="searchChart" style="max-height:300px;"></canvas>
        <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const salesData = <?= json_encode($topSales) ?>;
    const searchData = <?= json_encode($topSearches) ?>;

    // Chart de Ventas (Barras)
    if (document.getElementById('salesChart')) {
        new Chart(document.getElementById('salesChart'), {
            type: 'bar',
            data: {
                labels: salesData.map(d => d.referencia.substring(0, 15) + (d.referencia.length > 15 ? '...' : '')),
                datasets: [{
                    label: 'Cantidad Vendida',
                    data: salesData.map(d => d.total),
                    backgroundColor: '#238636',
                    borderRadius: 8
                }]
            },
            options: {
                indexAxis: 'y',
                scales: {
                    x: { beginAtZero: true, grid: { color: '#30363D' }, ticks: { color: '#8B949E' } },
                    y: { grid: { display: false }, ticks: { color: '#8B949E' } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }

    // Chart de Busquedas (Doughnut)
    if (document.getElementById('searchChart')) {
        new Chart(document.getElementById('searchChart'), {
            type: 'doughnut',
            data: {
                labels: searchData.map(d => d.label.substring(0, 20) + (d.label.length > 20 ? '...' : '')),
                datasets: [{
                    data: searchData.map(d => d.total),
                    backgroundColor: ['#58A6FF', '#D2A654', '#238636', '#F85149', '#8B949E'],
                    borderWidth: 0
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#F0F6FC', padding: 20, font: { family: 'Inter', size: 10 } }
                    }
                },
                cutout: '75%'
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
