<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

use Dompdf\Dompdf;
use Dompdf\Options;

$id = $_GET['id'] ?? null;
if (!$id) die("ID de orden no proporcionado.");

$db = getDB();
// Obtener Orden y Cliente
$stmt = $db->prepare("
    SELECT ov.*, c.nombre, c.apellido, c.cedula, c.telefono, u.nombre_completo as vendedor
    FROM ordenes_venta ov
    JOIN clientes c ON ov.cliente_id = c.id
    JOIN usuarios u ON ov.usuario_id = u.id
    WHERE ov.id = ?
");
$stmt->execute([$id]);
$orden = $stmt->fetch();

if (!$orden) die("Orden no encontrada.");

// Obtener Detalles
$stmt_det = $db->prepare("
    SELECT od.*, p.referencia 
    FROM ordenes_detalles od
    JOIN productos p ON od.producto_cod = p.codigop
    WHERE od.orden_id = ?
");
$stmt_det->execute([$id]);
$detalles = $stmt_det->fetchAll();

// Configurar Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);

// Generar HTML del Ticket
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: 80mm 200mm; margin: 0; }
        body { 
            font-family: 'Helvetica', sans-serif; 
            font-size: 10px; 
            padding: 10mm; 
            color: #111;
            line-height: 1.2;
        }
        .header { text-align: center; margin-bottom: 5mm; }
        .logo { width: 40mm; margin-bottom: 2mm; }
        .title { font-size: 14px; font-weight: bold; margin-bottom: 1mm; }
        .info { border-bottom: 1px dashed #ccc; padding-bottom: 2mm; margin-bottom: 2mm; }
        .info div { display: flex; justify-content: space-between; margin-bottom: 0.5mm; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 5mm; }
        .table th { border-bottom: 1px solid #111; text-align: left; padding: 1mm 0; }
        .table td { padding: 1.5mm 0; border-bottom: 0.5px solid #eee; }
        .totals { margin-top: 5mm; font-size: 11px; }
        .total-row { display: block; text-align: right; margin-bottom: 1mm; font-weight: bold; }
        .footer { text-align: center; margin-top: 10mm; font-size: 8px; color: #777; border-top: 1px dashed #ccc; padding-top: 2mm; }
        .badge { display: inline-block; padding: 1mm 2mm; border-radius: 1mm; font-size: 8px; color: #fff; margin-bottom: 2mm; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">EL REBUSQUE</div>
        <div style="font-size: 8px;">Sistema de Gestión de Ventas</div>
        <div style="font-size: 9px; margin-top: 2mm;"><b>TICKET DE VENTA #<?= $orden['id'] ?></b></div>
        <div class="badge" style="background: <?= $orden['estado'] === 'completado' ? '#238636' : '#f85149' ?>;">
            <?= strtoupper($orden['estado']) ?>
        </div>
    </div>

    <div class="info">
        <div><span><b>Fecha:</b></span> <span><?= date('d/m/Y H:i', strtotime($orden['created_at'])) ?></span></div>
        <div><span><b>Cliente:</b></span> <span><?= htmlspecialchars($orden['nombre'].' '.$orden['apellido']) ?></span></div>
        <div><span><b>Cédula:</b></span> <span><?= htmlspecialchars($orden['cedula']) ?></span></div>
        <div style="border-bottom: 1px dashed #eee; margin: 1mm 0;"></div>
        <div><span><b>Vendedor:</b></span> <span><?= htmlspecialchars($orden['vendedor']) ?></span></div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 50%;">Producto</th>
                <th style="width: 20%; text-align:center;">Cant.</th>
                <th style="width: 30%; text-align:right;">Subt.</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($detalles as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['referencia']) ?></td>
                    <td style="text-align:center;"><?= (float)$d['cantidad'] ?></td>
                    <td style="text-align:right;">$<?= number_format($d['subtotal_usd'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div class="total-row" style="font-size: 14px; color: #111;">
            TOTAL USD: $<?= number_format($orden['total_usd'], 2) ?>
        </div>
        <div class="total-row" style="color: #666; font-weight: normal;">
            Tasa: Bs. <?= number_format($orden['tasa_aplicada'], 2) ?>
        </div>
        <div class="total-row" style="font-size: 13px; color: #d2a8ff;">
            TOTAL BS: <?= number_format($orden['total_bs'], 2) ?> Bs.
        </div>
    </div>

    <div class="footer">
        ¡Gracias por su compra!<br>
        Este ticket es un comprobante de operación interna.<br>
        v<?= APP_VERSION ?>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

$dompdf->loadHtml($html);
$dompdf->setPaper([0, 0, 226.77, 500], 'portrait'); // 80mm de ancho
$dompdf->render();

// Salida
$dompdf->stream("Ticket_".$id.".pdf", ["Attachment" => false]);
?>
