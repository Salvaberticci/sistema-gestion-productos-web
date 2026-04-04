<?php 
$currentModule = $currentModule ?? ''; 
$pendingCount = 0;
if (isAdmin()) {
    try {
        $db = getDB();
        $pendingCount = $db->query("SELECT COUNT(*) FROM ordenes_venta WHERE estado = 'pendiente'")->fetchColumn();
    } catch (Exception $e) { $pendingCount = 0; }
}
?>
<div class="app-layout">
    <!-- Overlay para cerrar sidebar en movil -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="<?= APP_URL ?>/assets/images/logo.png" alt="Logo" class="sidebar-logo">
            <span class="sidebar-title">ADMIN PANEL</span>
        </div>
        <nav class="sidebar-nav">
<?php if (isAdmin()): ?>
            <a href="<?= APP_URL ?>/modules/dashboard/index.php" class="nav-item <?= $currentModule === 'dashboard' ? 'active' : '' ?>">
                <span class="nav-icon">📊</span><span class="nav-text">Dashboard</span>
            </a>
            <?php endif; ?>

            <a href="<?= APP_URL ?>/modules/ventas/index.php" class="nav-item <?= $currentModule === 'ventas' ? 'active' : '' ?>">
                <span class="nav-icon">🛒</span><span class="nav-text">Órdenes de Venta</span>
            </a>

            <?php if (isAdmin()): ?>
            <a href="<?= APP_URL ?>/modules/ventas_pendientes/index.php" class="nav-item <?= $currentModule === 'ventas_pendientes' ? 'active' : '' ?>">
                <span class="nav-icon">🔍</span>
                <span class="nav-text">Órdenes Pendientes</span>
                <?php if ($pendingCount > 0): ?>
                    <span class="nav-badge"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= APP_URL ?>/modules/inventario/index.php" class="nav-item <?= $currentModule === 'inventario' ? 'active' : '' ?>">
                <span class="nav-icon">📦</span><span class="nav-text">Inventario</span>
            </a>
            <?php endif; ?>

            <a href="<?= APP_URL ?>/modules/scanner/index.php" class="nav-item <?= $currentModule === 'scanner' ? 'active' : '' ?>">
                <span class="nav-icon">📸</span><span class="nav-text">Escáner</span>
            </a>

            <?php if (isAdmin()): ?>
            <a href="<?= APP_URL ?>/modules/impresion/index.php" class="nav-item <?= $currentModule === 'impresion' ? 'active' : '' ?>">
                <span class="nav-icon">🖨️</span><span class="nav-text">Impresión</span>
            </a>
            <?php endif; ?>
            <a href="<?= APP_URL ?>/modules/consulta/index.php" class="nav-item <?= $currentModule === 'consulta' ? 'active' : '' ?>">
                <span class="nav-icon">🔍</span><span class="nav-text">Consulta</span>
            </a>
            <?php if (isAdmin()): ?>
            <div class="nav-divider"></div>
            <a href="<?= APP_URL ?>/modules/usuarios/index.php" class="nav-item <?= $currentModule === 'usuarios' ? 'active' : '' ?>">
                <span class="nav-icon">👥</span><span class="nav-text">Usuarios</span>
            </a>
            <a href="<?= APP_URL ?>/modules/estadisticas/index.php" class="nav-item <?= $currentModule === 'estadisticas' ? 'active' : '' ?>">
                <span class="nav-icon">📈</span><span class="nav-text">Estadísticas</span>
            </a>
            <a href="<?= APP_URL ?>/modules/reportes/index.php" class="nav-item <?= $currentModule === 'reportes' ? 'active' : '' ?>">
                <span class="nav-icon">📜</span><span class="nav-text">Reportes y Cierres</span>
            </a>
            <a href="<?= APP_URL ?>/modules/sistema/index.php" class="nav-item <?= $currentModule === 'sistema' ? 'active' : '' ?>">
                <span class="nav-icon">🔄</span><span class="nav-text">Sincronización</span>
            </a>
            <a href="<?= APP_URL ?>/modules/configuracion/index.php" class="nav-item <?= $currentModule === 'configuracion' ? 'active' : '' ?>">
                <span class="nav-icon">⚙️</span><span class="nav-text">Configuración</span>
            </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?></div>
                <div class="user-details">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span>
                    <span class="user-role"><?= ucfirst($_SESSION['user_rol'] ?? '') ?></span>
                </div>
            </div>
            <a href="<?= APP_URL ?>/logout.php" class="nav-item logout-btn">
                <span class="nav-icon">🚪</span><span class="nav-text">Cerrar Sesión</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar Mobile -->
        <header class="topbar">
            <button class="hamburger" id="hamburgerBtn" onclick="toggleSidebar()">
                <span></span><span></span><span></span>
            </button>
            <h1 class="topbar-title"><?= $pageTitle ?? 'Dashboard' ?></h1>
            <div class="topbar-actions">
                <span class="topbar-role-badge <?= isAdmin() ? 'badge-admin' : 'badge-employee' ?>"><?= ucfirst($_SESSION['user_rol'] ?? '') ?></span>
            </div>
        </header>
        <div class="content-wrapper">
