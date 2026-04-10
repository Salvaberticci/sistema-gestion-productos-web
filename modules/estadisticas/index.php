<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$db = getDB();

// Filtro de fecha
$fecha_filtro = $_GET['fecha'] ?? date('Y-m-d'); // Por defecto, hoy.

// Datos para Grafica 1: Top 5 Stock
$topStock = $db->query("SELECT referencia, exisact FROM productos ORDER BY exisact DESC LIMIT 5")->fetchAll();
// Datos para Grafica 2: Top 5 Busquedas
$topSearches = $db->query("SELECT referencia, busquedas FROM productos WHERE busquedas > 0 ORDER BY busquedas DESC LIMIT 5")->fetchAll();

// Productividad de Personal (Asumiendo que `historial_busquedas` y `ordenes_venta` tienen fechas)
if (empty($fecha_filtro)) {
    // Todos los tiempos
    $productividad = $db->query("
        SELECT 
            u.id, u.nombre_completo, u.username, u.rol, u.activo,
            (SELECT COUNT(*) FROM historial_busquedas hb WHERE hb.usuario_id = u.id) as consultas_hoy,
            (SELECT COUNT(*) FROM ordenes_venta ov WHERE ov.usuario_id = u.id) as ordenes_hoy
        FROM usuarios u
        ORDER BY ordenes_hoy DESC, consultas_hoy DESC
    ")->fetchAll();
} else {
    // Filtrado por fecha
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

$pageTitle = 'Estadísticas Avanzadas';
$currentModule = 'estadisticas';
$extraScripts = ['https://cdn.jsdelivr.net/npm/chart.js'];
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-header">
    <h2 class="page-title">📈 Estadísticas y Métricas</h2>
</div>

<!-- Filtro Integrado -->
<div class="card mb-4">
    <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
        <div style="flex:1;">
            <label class="form-label" style="font-size:0.85rem; font-weight:700; color:var(--color-text-dim);">Productividad del Personal por Fecha</label>
            <input type="date" name="fecha" class="form-input" value="<?= htmlspecialchars($fecha_filtro) ?>" max="<?= date('Y-m-d') ?>">
            <p style="font-size:0.75rem; color:var(--color-text-dim); margin-top:5px;">Borre la fecha para ver el histórico global.</p>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary" style="height: 52px; padding: 0 25px;">
                🔍 Filtrar Productividad
            </button>
            <a href="index.php" class="btn btn-secondary" style="height: 52px; padding: 0 25px; line-height: 52px; text-decoration: none; text-align: center;">
                🔄 Hoy
            </a>
        </div>
    </form>
</div>

<!-- Grilla de Empleados (Productividad) -->
<h3 class="card-title mb-3 px-2">👥 Desempeño del Personal <?= empty($fecha_filtro) ? '(Historico Global)' : '(Datos del: '.date('d/m/Y', strtotime($fecha_filtro)).')' ?></h3>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-5">
    <?php foreach ($productividad as $u): ?>
        <div class="card p-4" style="border-left: 4px solid <?= $u['rol'] === 'admin' ? 'var(--color-accent)' : 'var(--color-gold)' ?>;">
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
                    <div style="font-size:0.65rem; color:var(--color-text-dim); text-transform:uppercase; font-weight:800; letter-spacing:0.5px; margin-bottom:5px;">Órdenes (Tickets)</div>
                    <div style="color:var(--color-gold); font-weight:900; font-size:1.5rem; line-height:1;"><?= number_format($u['ordenes_hoy']) ?></div>
                </div>
            </div>
            <?php if (!$u['activo']): ?>
                <div class="mt-3 text-center text-danger" style="font-size:0.75rem; font-weight:800;">(USUARIO INACTIVO)</div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="card">
        <h3 class="card-title mb-2">📦 Distribución de Inventario (Top 5)</h3>
        <canvas id="stockChart" style="max-height:300px;"></canvas>
    </div>

    <div class="card">
        <h3 class="card-title mb-2">🔥 Interés del Cliente (Top 5 Todos los Tiempos)</h3>
        <canvas id="searchChart" style="max-height:300px;"></canvas>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const stockData = <?= json_encode($topStock) ?>;
    const searchData = <?= json_encode($topSearches) ?>;

    // Chart de Stock
    new Chart(document.getElementById('stockChart'), {
        type: 'bar',
        data: {
            labels: stockData.map(d => d.referencia.substring(0, 15) + '...'),
            datasets: [{
                label: 'Unidades en Stock',
                data: stockData.map(d => d.exisact),
                backgroundColor: '#58A6FF',
                borderRadius: 8
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true, grid: { color: '#30363D' }, ticks: { color: '#8B949E' } },
                x: { grid: { display: false }, ticks: { color: '#8B949E' } }
            },
            plugins: { legend: { display: false } }
        }
    });

    // Chart de Busquedas
    new Chart(document.getElementById('searchChart'), {
        type: 'doughnut',
        data: {
            labels: searchData.map(d => d.referencia.substring(0, 15)),
            datasets: [{
                data: searchData.map(d => d.busquedas),
                backgroundColor: ['#58A6FF', '#D1A054', '#238636', '#F85149', '#8B949E'],
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
            cutout: '70%'
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
