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
    <div class="product-cards p-3">
        <?php foreach ($usuarios as $u): ?>
        <div class="card p-4 mb-3" style="border-left: 4px solid <?= $u['rol'] === 'admin' ? 'var(--color-accent)' : 'var(--color-gold)' ?>;">
            <div class="flex items-center gap-4 mb-4">
                <div class="user-avatar" style="width:50px; height:50px; font-size:1.25rem;">
                    <?= strtoupper(substr($u['nombre_completo'], 0, 1)) ?>
                </div>
                <div class="flex-1">
                    <div class="text-white font-bold text-lg leading-tight mb-1">
                        <?= htmlspecialchars($u['nombre_completo']) ?>
                    </div>
                    <div class="text-dim text-sm">
                        <span class="text-accent opacity-80">@</span><?= htmlspecialchars($u['username']) ?> 
                        <span class="mx-1">•</span> 
                        <span class="<?= $u['rol'] === 'admin' ? 'text-accent' : 'text-gold' ?> font-bold text-xs uppercase tracking-widest">
                            <?= ucfirst($u['rol']) ?>
                        </span>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <a href="editar.php?id=<?= $u['id'] ?>" class="btn btn-secondary btn-sm" style="padding:0 12px; width:40px; border-radius:10px;">✏️</a>
                    <?php if ($u['id'] != $_SESSION['user_id'] && $u['activo']): ?>
                        <button onclick="eliminarUsuario(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nombre_completo'])) ?>')" class="btn btn-danger btn-sm" style="padding:0 12px; width:40px; border-radius:10px;">🗑️</button>
                    <?php elseif (!$u['activo']): ?>
                        <a href="toggle_status.php?id=<?= $u['id'] ?>" class="btn btn-success btn-sm" style="padding:0 12px; width:40px; border-radius:10px;">✅</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-1">
                <div class="bg-dark-lighter/50 p-3 rounded-xl border border-border/50 text-center">
                    <div class="text-[10px] text-dim uppercase font-bold tracking-wider mb-1">Consultas</div>
                    <div class="text-accent font-black text-lg"><?= number_format($u['consultas_realizadas']) ?></div>
                </div>
                <div class="bg-dark-lighter/50 p-3 rounded-xl border border-border/50 text-center">
                    <div class="text-[10px] text-dim uppercase font-bold tracking-wider mb-1">Órdenes</div>
                    <div class="text-gold font-black text-lg"><?= number_format($u['orders_count']) ?></div>
                </div>
            </div>

            <div class="mt-3 pt-3 border-t border-border/30 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full <?= $u['activo'] ? 'bg-success ripple-success' : 'bg-danger' ?>"></div>
                    <span class="<?= $u['activo'] ? 'text-success' : 'text-danger' ?> font-bold text-[10px] uppercase tracking-tighter">
                        <?= $u['activo'] ? 'Usuario Activo en Sistema' : 'Cuenta Inactivada / Bloqueada' ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.ripple-success {
    box-shadow: 0 0 0 0 rgba(35, 134, 54, 0.4);
    animation: ripple 1.5s infinite;
}
@keyframes ripple {
    0% { box-shadow: 0 0 0 0 rgba(35, 134, 54, 0.4); }
    70% { box-shadow: 0 0 0 6px rgba(35, 134, 54, 0); }
    100% { box-shadow: 0 0 0 0 rgba(35, 134, 54, 0); }
}
</style>

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
