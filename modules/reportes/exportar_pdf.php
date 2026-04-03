<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

use Dompdf\Dompdf;
use Dompdf\Options;

$db = getDB();
$rate = getExchangeRate();

// Filtros de fecha (por defecto hoy)
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-d');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

// 1. Resumen Financiero
$stmt_resumen = $db->prepare("
    SELECT 
        COUNT(*) as total_tickets,
        COALESCE(SUM(total_usd), 0) as ingresos_usd,
        COALESCE(SUM(total_bs), 0) as ingresos_bs,
        (SELECT COUNT(*) FROM ordenes_venta WHERE estado = 'rechazado' AND DATE(created_at) BETWEEN ? AND ?) as total_rechazados
    FROM ordenes_venta 
    WHERE estado = 'completado' AND DATE(created_at) BETWEEN ? AND ?
");
$stmt_resumen->execute([$fecha_desde, $fecha_hasta, $fecha_desde, $fecha_hasta]);
$resumen = $stmt_resumen->fetch();

// 2. Ranking de Productos
$stmt_top_prod = $db->prepare("
    SELECT p.referencia, SUM(od.cantidad) as total_vendido, SUM(od.subtotal_usd) as total_dinero
    FROM ordenes_detalles od
    JOIN productos p ON od.producto_cod = p.codigop
    JOIN ordenes_venta ov ON od.orden_id = ov.id
    WHERE ov.estado = 'completado' AND DATE(ov.created_at) BETWEEN ? AND ?
    GROUP BY od.producto_cod
    ORDER BY total_vendido DESC
    LIMIT 10
");
$stmt_top_prod->execute([$fecha_desde, $fecha_hasta]);
$top_productos = $stmt_top_prod->fetchAll();

// 3. Ranking de Clientes
$stmt_top_cli = $db->prepare("
    SELECT c.nombre, c.apellido, COUNT(ov.id) as compras_realizadas, SUM(ov.total_usd) as total_generado
    FROM ordenes_venta ov
    JOIN clientes c ON ov.cliente_id = c.id
    WHERE ov.estado = 'completado' AND DATE(ov.created_at) BETWEEN ? AND ?
    GROUP BY ov.cliente_id
    ORDER BY total_generado DESC
    LIMIT 10
");
$stmt_top_cli->execute([$fecha_desde, $fecha_hasta]);
$top_clientes = $stmt_top_cli->fetchAll();

// 4. Historial Detallado
$stmt_historial = $db->prepare("
    SELECT ov.*, c.nombre, c.apellido, u.nombre_completo as vendedor
    FROM ordenes_venta ov
    JOIN clientes c ON ov.cliente_id = c.id
    JOIN usuarios u ON ov.usuario_id = u.id
    WHERE DATE(ov.created_at) BETWEEN ? AND ?
    ORDER BY ov.created_at DESC
");
$stmt_historial->execute([$fecha_desde, $fecha_hasta]);
$historial = $stmt_historial->fetchAll();

// Configurar Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas - El Rebusque</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; font-size: 11px; margin: 0; padding: 30px; }
        .header { border-bottom: 2px solid #58A6FF; padding-bottom: 15px; margin-bottom: 25px; }
        .header table { width: 100%; }
        .company-name { font-size: 24px; font-weight: bold; color: #58A6FF; }
        .report-title { font-size: 16px; font-weight: bold; margin-top: 5px; color: #555; }
        .date-range { font-size: 11px; color: #777; margin-top: 5px; }
        
        .section-title { background: #f4f4f4; padding: 8px 12px; font-size: 13px; font-weight: bold; border-left: 4px solid #58A6FF; margin: 20px 0 10px; }
        
        .stats-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .stats-table td { padding: 15px; border: 1px solid #eee; width: 25%; text-align: center; }
        .stat-label { font-size: 10px; color: #888; text-transform: uppercase; margin-bottom: 5px; }
        .stat-value { font-size: 16px; font-weight: bold; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th { background: #58A6FF; color: #fff; padding: 10px; text-align: left; }
        .data-table td { padding: 8px 10px; border-bottom: 1px solid #eee; }
        .data-table tr:nth-child(even) { background: #fcfcfc; }
        
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .text-success { color: #238636; }
        .text-danger { color: #f85149; }
        .text-accent { color: #58A6FF; font-weight: bold; }
        
        .footer { position: fixed; bottom: 20px; left: 30px; right: 30px; border-top: 1px solid #eee; padding-top: 10px; font-size: 9px; color: #aaa; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td style="width: 70%;">
                    <div class="company-name">EL REBUSQUE</div>
                    <div class="report-title">CIERRE DE ÓRDENES DE VENTAS</div>
                    <div class="date-range">Periodo: <?= date('d/m/Y', strtotime($fecha_desde)) ?> - <?= date('d/m/Y', strtotime($fecha_hasta)) ?></div>
                </td>
                <td style="width: 30%; text-align: right;">
                    <div style="font-size: 10px; color: #777;">Fecha Impresión: <?= date('d/m/Y H:i') ?></div>
                    <div style="font-size: 10px; color: #777;">Tasa del día: Bs. <?= number_format($rate, 2) ?></div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Resumen Ejecutivo</div>
    <table class="stats-table">
        <tr>
            <td>
                <div class="stat-label">Ventas Aprobadas</div>
                <div class="stat-value text-success"><?= $resumen['total_tickets'] ?></div>
            </td>
            <td>
                <div class="stat-label">Ventas Rechazadas</div>
                <div class="stat-value text-danger"><?= $resumen['total_rechazados'] ?></div>
            </td>
            <td>
                <div class="stat-label">Ingresos USD</div>
                <div class="stat-value" style="color: #D1A054;">$<?= number_format($resumen['ingresos_usd'], 2) ?></div>
            </td>
            <td>
                <div class="stat-label">Ingresos Bs.</div>
                <div class="stat-value text-accent"><?= number_format($resumen['ingresos_bs'], 2) ?> Bs.</div>
            </td>
        </tr>
    </table>

    <div style="width: 100%;">
        <div style="width: 48%; float: left;">
            <div class="section-title">Top Productos</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 60%;">Producto</th>
                        <th class="text-center">Cant.</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($top_productos as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['referencia']) ?></td>
                            <td class="text-center"><?= (int)$p['total_vendido'] ?></td>
                            <td class="text-right">$<?= number_format($p['total_dinero'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="width: 48%; float: right;">
            <div class="section-title">Top Clientes</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 60%;">Cliente</th>
                        <th class="text-center">Tks</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($top_clientes as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['nombre'].' '.$c['apellido']) ?></td>
                            <td class="text-center"><?= $c['compras_realizadas'] ?></td>
                            <td class="text-right">$<?= number_format($c['total_generado'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="section-title">Historial de Operaciones</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 8%;">ID</th>
                <th style="width: 12%;">Estado</th>
                <th style="width: 25%;">Cliente</th>
                <th style="width: 20%;">Vendedor</th>
                <th style="width: 15%;" class="text-right">Monto USD</th>
                <th style="width: 20%;" class="text-right">Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($historial as $h): ?>
                <tr>
                    <td>#<?= $h['id'] ?></td>
                    <td class="<?= $h['estado'] === 'completado' ? 'text-success' : 'text-danger' ?>">
                        <?= strtoupper($h['estado']) ?>
                    </td>
                    <td><?= htmlspecialchars($h['nombre'].' '.$h['apellido']) ?></td>
                    <td><?= htmlspecialchars($h['vendedor']) ?></td>
                    <td class="text-right text-accent">$<?= number_format($h['total_usd'], 2) ?></td>
                    <td class="text-right"><?= date('d/m/y H:i', strtotime($h['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        El Rebusque - Sistema de Gestión de Productos - Reporte Generado por <?= $_SESSION['usuario_nombre'] ?><br>
        v<?= APP_VERSION ?> | Página 1
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = "Reporte_Fventas_" . date('Ymd', strtotime($fecha_desde)) . "_al_" . date('Ymd', strtotime($fecha_hasta)) . ".pdf";
$dompdf->stream($filename, ["Attachment" => false]);
?>
