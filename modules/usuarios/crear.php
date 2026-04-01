<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre_completo'] ?? '');
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? 'empleado';

    if (empty($nombre) || empty($user) || empty($pass)) {
        $error = 'Por favor complete todos los campos requeridos.';
    } else {
        $db = getDB();
        
        // Verificar si el usuario ya existe
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE username = ?");
        $stmt->execute([$user]);
        if ($stmt->fetch()) {
            $error = 'El nombre de usuario ya está en uso.';
        } else {
            try {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO usuarios (username, password, nombre_completo, rol) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user, $hash, $nombre, $rol]);
                $success = 'Usuario creado con éxito.';
            } catch (Exception $e) {
                $error = 'Error al crear el usuario: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Nuevo Usuario';
$currentModule = 'usuarios';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-header">
    <h2 class="page-title">➕ Crear Nuevo Usuario</h2>
    <a href="index.php" class="btn btn-secondary btn-sm">← Volver</a>
</div>

<div class="card">
    <?php if ($error): ?>
        <div class="login-error" style="margin-bottom:20px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div style="background:var(--color-success); color:#fff; padding:12px; border-radius:10px; margin-bottom:20px; text-align:center;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label class="form-label">Nombre Completo</label>
            <input type="text" name="nombre_completo" class="form-input" placeholder="Nombre real del empleado" required value="<?= htmlspecialchars($_POST['nombre_completo'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Nombre de Usuario (@)</label>
            <input type="text" name="username" class="form-input" placeholder="Ej: jsmith" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Contraseña</label>
            <input type="password" name="password" class="form-input" placeholder="Contraseña segura" required>
        </div>

        <div class="form-group">
            <label class="form-label">Rol del Sistema</label>
            <select name="rol" class="form-input" style="appearance: none; -webkit-appearance: none; cursor:pointer;">
                <option value="empleado" <?= ($_POST['rol'] ?? '') === 'empleado' ? 'selected' : '' ?>>Empleado (Inventario e Impresión)</option>
                <option value="admin" <?= ($_POST['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador (Acceso Total)</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-lg mt-2">
            Guardar Usuario
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
