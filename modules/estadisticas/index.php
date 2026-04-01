?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$db = getDB();
// Datos para Grafica 1: Top 5 Stock
$topStock = $db->query("SELECT referencia, exisact FROM productos ORDER BY exisact DESC LIMIT 5")->fetchAll();
// Datos para Grafica 2: Top 5 Busquedas
$topSearches = $db->query("SELECT referencia, busquedas FROM productos WHERE busquedas > 0 ORDER BY busquedas DESC LIMIT 5")->fetchAll();

$pageTitle = 'Estadísticas Avanzadas';
$currentModule = 'estadisticas';
$extraScripts = ['https://cdn.jsdelivr.net/npm/chart.js'];
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-header">
    <h2 class="page-title">📈 Estadísticas y Métricas</h2>
</div>

<div class="card">
    <h3 class="card-title mb-2">📦 Distribución de Inventario (Top 5)</h3>
    <canvas id="stockChart" style="max-height:300px;"></canvas>
</div>

<div class="card mt-3">
    <h3 class="card-title mb-2">🔥 Interés del Cliente (Top 5 Buscados)</h3>
    <canvas id="searchChart" style="max-height:300px;"></canvas>
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
