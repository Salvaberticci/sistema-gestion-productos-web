?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$u = $stmt->fetch();

if (!$u) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre_completo'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? 'empleado';

    if (empty($nombre) || empty($username)) {
        $error = 'Nombre y Usuario son obligatorios.';
    } else {
        // Verificar unicidad de username
        $stmt_check = $db->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
        $stmt_check->execute([$username, $id]);
        if ($stmt_check->fetch()) {
            $error = 'El nombre de usuario ya está en uso por otro empleado.';
        } else {
            try {
                if (!empty($pass)) {
                    $hash = password_hash($pass, PASSWORD_DEFAULT);
                    $stmt_upd = $db->prepare("UPDATE usuarios SET username = ?, password = ?, nombre_completo = ?, rol = ? WHERE id = ?");
                    $stmt_upd->execute([$username, $hash, $nombre, $rol, $id]);
                } else {
                    $stmt_upd = $db->prepare("UPDATE usuarios SET username = ?, nombre_completo = ?, rol = ? WHERE id = ?");
                    $stmt_upd->execute([$username, $nombre, $rol, $id]);
                }
                
                // Actualizar sesion si es el mismo usuario
                if ($id == $_SESSION['user_id']) {
                    $_SESSION['user_name'] = $nombre;
                    $_SESSION['user_username'] = $username;
                    $_SESSION['user_rol'] = $rol;
                }
                
                $success = 'Usuario actualizado con éxito.';
                // Regraficar los datos locales para el formulario
                $stmt->execute([$id]);
                $u = $stmt->fetch();
            } catch (Exception $e) {
                $error = 'Error al actualizar: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Editar Usuario';
$currentModule = 'usuarios';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-header">
    <h2 class="page-title">✏️ Editar Usuario: <?= htmlspecialchars($u['username']) ?></h2>
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
            <input type="text" name="nombre_completo" class="form-input" required value="<?= htmlspecialchars($u['nombre_completo']) ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Nombre de Usuario (@)</label>
            <input type="text" name="username" class="form-input" required value="<?= htmlspecialchars($u['username']) ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Nueva Contraseña (dejar en blanco para no cambiar)</label>
            <input type="password" name="password" class="form-input" placeholder="Nueva contraseña segura">
        </div>

        <div class="form-group">
            <label class="form-label">Rol del Sistema</label>
            <select name="rol" class="form-input" style="appearance: none; -webkit-appearance: none; cursor:pointer;" <?= ($_SESSION['user_id'] == $id) ? 'disabled' : '' ?>>
                <option value="empleado" <?= $u['rol'] === 'empleado' ? 'selected' : '' ?>>Empleado (Inventario e Impresión)</option>
                <option value="admin" <?= $u['rol'] === 'admin' ? 'selected' : '' ?>>Administrador (Acceso Total)</option>
            </select>
            <?php if ($_SESSION['user_id'] == $id): ?>
                <input type="hidden" name="rol" value="<?= $u['rol'] ?>">
                <p class="text-dim mt-1" style="font-size:0.75rem;">Nota: No puedes cambiar tu propio rol.</p>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary btn-lg mt-2">
            Guardar Cambios
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
