<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$db = getDB();
$pendingOrders = $db->query("
    SELECT ov.*, c.nombre, c.apellido 
    FROM ordenes_venta ov 
    JOIN clientes c ON ov.cliente_id = c.id 
    WHERE ov.estado = 'pendiente' 
    ORDER BY ov.created_at ASC
")->fetchAll();

if (empty($pendingOrders)):
    echo ""; // Vacío si no hay nada
    exit;
endif;

$rate = getExchangeRate();
?>

<!-- Ventas por Aprobar (Fragmento) -->
<div class="card p-0 mb-4" style="border-top: 4px solid var(--color-danger); animation: fadeIn 0.3s ease-out;">
    <div class="card-header flex justify-between items-center" style="padding: 15px;">
        <h3 class="card-title">🚨 Ventas por Aprobar</h3>
        <span class="badge-admin" style="font-size: 0.75rem;"><?= count($pendingOrders) ?> PENDIENTE<?= count($pendingOrders) !== 1 ? 'S' : '' ?></span>
    </div>

    <!-- Vista Móvil (Tarjetas) -->
    <div class="product-cards p-3" style="display: flex; border-top: 1px solid var(--color-border); flex-direction: column; gap: 10px;">
        <?php foreach ($pendingOrders as $o): ?>
        <div class="product-card" onclick="viewOrder(<?= $o['id'] ?>)" style="width: 100%; cursor: pointer; margin: 0;">
            <div class="product-card-info">
                <div class="product-card-name" style="font-size: 1rem;">
                    <span class="text-dim">#<?= $o['id'] ?></span> <?= htmlspecialchars($o['nombre'] . ' ' . $o['apellido']) ?>
                </div>
                <div class="product-card-code"><?= date('d/m/y H:i', strtotime($o['created_at'])) ?></div>
            </div>
            <div class="product-card-price" style="display:flex; flex-direction:column; align-items:flex-end; gap:2px;">
                <span><?= formatCurrency($o['total_usd']) ?></span>
                <small style="font-size:0.7rem; color:var(--color-gold);"><?= formatCurrency($o['total_bs'], 'Bs.') ?></small>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Vista Desktop (Tabla) -->
    <div class="table-responsive desktop-only">
        <table class="data-table" style="display: table;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Monto</th>
                    <th>Fecha</th>
                    <th style="text-align:right;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingOrders as $o): ?>
                <tr>
                    <td><span class="text-dim">#</span><?= $o['id'] ?></td>
                    <td><span class="fw-bold"><?= htmlspecialchars($o['nombre'] . ' ' . $o['apellido']) ?></span></td>
                    <td>
                        <div class="text-accent fw-bold"><?= formatCurrency($o['total_usd']) ?></div>
                        <div class="text-dim" style="font-size:0.7rem;"><?= formatCurrency((float)$o['total_bs'], 'Bs.') ?></div>
                    </td>
                    <td><?= date('d/m/y H:i', strtotime($o['created_at'])) ?></td>
                    <td style="text-align:right;">
                        <button onclick="viewOrder(<?= $o['id'] ?>)" class="btn btn-primary btn-sm">👁️ Detalle</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
