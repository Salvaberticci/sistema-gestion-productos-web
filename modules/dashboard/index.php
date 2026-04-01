?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

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

$pageTitle = 'Dashboard';
$currentModule = 'dashboard';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-header">
    <h2 class="page-title">Panel Principal</h2>
    <span class="text-dim" style="font-size:0.8125rem;">Tasa: <?= formatCurrency($rate, 'Bs.') ?></span>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
