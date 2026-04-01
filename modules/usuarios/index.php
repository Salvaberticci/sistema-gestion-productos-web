<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin(); // Solo el Admin puede ver esto

$db = getDB();
$usuarios = $db->query("SELECT * FROM usuarios ORDER BY id DESC")->fetchAll();

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
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <a href="toggle_status.php?id=<?= $u['id'] ?>" class="btn btn-danger btn-sm" title="<?= $u['activo'] ? 'Desactivar' : 'Activar' ?>">
                                    <?= $u['activo'] ? '🚫' : '✅' ?>
                                </a>
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
                <div class="mt-1">
                    <?php if ($u['activo']): ?>
                        <span class="text-success" style="font-size:0.75rem; font-weight:700;">Activo</span>
                    <?php else: ?>
                        <span class="text-danger" style="font-size:0.75rem; font-weight:700;">Inactivo</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <a href="editar.php?id=<?= $u['id'] ?>" class="btn btn-secondary btn-sm">✏️</a>
                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                    <a href="toggle_status.php?id=<?= $u['id'] ?>" class="btn <?= $u['activo'] ? 'btn-danger' : 'btn-success' ?> btn-sm">
                        <?= $u['activo'] ? '🚫' : '✅' ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
