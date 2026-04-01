?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/modules/dashboard/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (login($username, $password)) {
        header('Location: ' . APP_URL . '/modules/dashboard/index.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos';
    }
}

$pageTitle = 'Iniciar Sesión';
require_once __DIR__ . '/includes/header.php';
?>
<div class="login-page">
    <div class="login-card">
        <img src="<?= APP_URL ?>/assets/images/logo.png" alt="El Rebusque" class="login-logo">
        <h1 class="login-title">Bienvenido</h1>
        <p class="login-subtitle">Ingresa tus credenciales para acceder al sistema</p>
        
        <?php if ($error): ?>
            <div class="login-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Usuario</label>
                <input type="text" name="username" class="form-input" placeholder="Ingresa tu usuario" required autofocus
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-input" placeholder="Ingresa tu contraseña" required>
            </div>
            <button type="submit" class="btn btn-primary btn-lg">
                Iniciar Sesión
            </button>
        </form>
        
        <p style="text-align:center; margin-top:25px; font-size:0.75rem; color:var(--color-text-dim);">
            Sistema de Gestión e Inventario v<?= APP_VERSION ?>
        </p>
    </div>
</div>
</body>
</html>
