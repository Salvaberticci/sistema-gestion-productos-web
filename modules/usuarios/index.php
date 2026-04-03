<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin(); // Solo el Admin puede ver esto

$db = getDB();
$usuarios = $db->query("
    SELECT u.*, 
    (SELECT COUNT(*) FROM ordenes_venta ov WHERE ov.usuario_id = u.id) as orders_count 
    FROM usuarios u 
    ORDER BY u.id DESC
")->fetchAll();

$pageTitle = 'Gestión de Usuarios';
$currentModule = 'usuarios';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="page-header">
    <h2 class="page-title">Gestión de Usuarios</h2>
    <a href="crear.php" class="btn btn-primary btn-sm">
        <span>➕</span> Nuevo Usuario
    </a>
</div>

<div class="card p-0">
    <!-- Tabla Desktop -->
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th style="text-align:center;">Consultas</th>
                    <th style="text-align:center;">Órdenes</th>
                    <th>Estado</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="user-avatar" style="width:30px; height:30px; font-size:0.75rem;">
                                <?= strtoupper(substr($u['nombre_completo'], 0, 1)) ?>
                            </div>
                            <span class="fw-bold"><?= htmlspecialchars($u['nombre_completo']) ?></span>
                        </div>
                    </td>
                    <td><span class="text-dim">@</span><?= htmlspecialchars($u['username']) ?></td>
                    <td>
                        <span class="topbar-role-badge <?= $u['rol'] === 'admin' ? 'badge-admin' : 'badge-employee' ?>">
                            <?= ucfirst($u['rol']) ?>
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <span style="font-weight:700; color:var(--color-accent);"><?= number_format($u['consultas_realizadas']) ?></span>
                    </td>
                    <td style="text-align:center;">
                        <span style="font-weight:700; color:var(--color-gold);"><?= number_format($u['orders_count']) ?></span>
                    </td>
                    <td>
                        <?php if ($u['activo']): ?>
                            <span class="text-success fw-bold">✓ Activo</span>
                        <?php else: ?>
                            <span class="text-danger fw-bold">✗ Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;">
                        <div class="flex justify-end gap-1">
                            <a href="editar.php?id=<?= $u['id'] ?>" class="btn btn-secondary btn-sm" title="Editar">✏️</a>
                            <?php if ($u['id'] != $_SESSION['user_id'] && $u['activo']): ?>
                                <button onclick="eliminarUsuario(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nombre_completo'])) ?>')" class="btn btn-danger btn-sm" title="Inactivar Vendedor">
                                    🗑️
                                </button>
                            <?php elseif (!$u['activo']): ?>
                                <a href="toggle_status.php?id=<?= $u['id'] ?>" class="btn btn-success btn-sm" title="Reactivar">✅</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Cards Mobile -->
    <div class="product-cards p-2">
        <?php foreach ($usuarios as $u): ?>
        <div class="product-card">
            <div class="product-card-img">
                <?= strtoupper(substr($u['nombre_completo'], 0, 1)) ?>
            </div>
            <div class="product-card-info">
                <div class="product-card-name"><?= htmlspecialchars($u['nombre_completo']) ?></div>
                <div class="product-card-code">@<?= htmlspecialchars($u['username']) ?> • <span class="<?= $u['rol'] === 'admin' ? 'text-accent' : 'text-gold' ?>"><?= ucfirst($u['rol']) ?></span></div>
                <div class="mt-1 flex items-center gap-2">
                    <?php if ($u['activo']): ?>
                        <span class="text-success" style="font-size:0.75rem; font-weight:700;">✓ Activo</span>
                    <?php else: ?>
                        <span class="text-danger" style="font-size:0.75rem; font-weight:700;">✗ Inactivo</span>
                    <?php endif; ?>
                    <span style="font-size:0.6875rem; color:var(--color-text-dim);">| Consultas: <strong class="text-accent"><?= number_format($u['consultas_realizadas']) ?></strong> | Órdenes: <strong class="text-gold"><?= number_format($u['orders_count']) ?></strong></span>
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <a href="editar.php?id=<?= $u['id'] ?>" class="btn btn-secondary btn-sm">✏️</a>
                <?php if ($u['id'] != $_SESSION['user_id'] && $u['activo']): ?>
                    <button onclick="eliminarUsuario(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nombre_completo'])) ?>')" class="btn btn-danger btn-sm">🗑️</button>
                <?php elseif (!$u['activo']): ?>
                    <a href="toggle_status.php?id=<?= $u['id'] ?>" class="btn btn-success btn-sm">✅</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function eliminarUsuario(id, nombre) {
    Swal.fire({
        title: 'Validación de Seguridad',
        html: `Estás a punto de inactivar al usuario <b>${nombre}</b>.<br><br>Ingresa tu contraseña de Administrador para confirmar:`,
        input: 'password',
        inputAttributes: {
            autocapitalize: 'off',
            autocorrect: 'off'
        },
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f85149',
        confirmButtonText: 'Verificar y Eliminar',
        cancelButtonText: 'Cancelar',
        background: '#0D1117',
        color: '#ffffff',
        showLoaderOnConfirm: true,
        preConfirm: (password) => {
            if (!password) {
                Swal.showValidationMessage('La contraseña es obligatoria');
                return false;
            }
            return fetch('seguridad_borrado.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}&password=${encodeURIComponent(password)}`
            })
            .then(response => {
                if (!response.ok) { throw new Error(response.statusText) }
                return response.json()
            })
            .catch(error => {
                Swal.showValidationMessage(`Error en la red: ${error}`)
            })
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            if(result.value.success) {
                Swal.fire({
                    title: '¡Eliminado!',
                    text: 'El usuario ha sido inactivado.',
                    icon: 'success',
                    background: '#0D1117',
                    color: '#ffffff',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    title: 'Acceso Denegado',
                    text: result.value.error || 'Contraseña incorrecta.',
                    icon: 'error',
                    background: '#0D1117',
                    color: '#ffffff'
                });
            }
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
