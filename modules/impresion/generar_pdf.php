<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../../assets/vendor/fpdf/fpdf.php';

$cod = $_GET['cod'] ?? null;
if (!$cod) {
    die("Código de producto no proporcionado.");
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM productos WHERE codigop = ?");
$stmt->execute([$cod]);
$p = $stmt->fetch();

if (!$p) {
    die("Producto no encontrado.");
}

$rate = getExchangeRate();
$price_bs = $p['pventa'] * $rate;
$product_name = utf8_decode($p['referencia']); // FPDF requiere ISO-8859-1 para fuentes estándar

// 1. Configuración de página exacta igual a Python (57mm x 80mm)
$pdf = new FPDF('P', 'mm', [57, 80]);
$pdf->AddPage();
$pdf->SetMargins(5, 5, 5); // Márgenes de 5 a la izquierda, arriba y derecha
$pdf->SetAutoPageBreak(false);

// 2. Algoritmo de escalado de fuente para el NOMBRE (IDÉNTICO a Python)
$font_size = 14;
$pdf->SetFont('Helvetica', 'B', $font_size);

// Reducir tamaño de fuente progresivamente si el texto excede el ancho útil (47mm)
while ($pdf->GetStringWidth($product_name) > 47 && $font_size > 7) {
    $font_size -= 0.5;
    $pdf->SetFont('Helvetica', 'B', $font_size);
}

$pdf->SetY(8);
// Usamos MultiCell para permitir que el nombre ocupe múltiples líneas si es largo, alineado a la Izquierda ('L')
$pdf->MultiCell(47, 5, $product_name, 0, 'L');

// 3. Dibujar el PRECIO
$pdf->SetY($pdf->GetY() + 4);
$pdf->SetFont('Helvetica', 'B', 18);
$formatted_price = "Bs. " . number_format($price_bs, 2, '.', ',');
$pdf->Cell(47, 10, $formatted_price, 0, 1, 'L');

// Guardar archivo/Mostrar en el navegador
$pdf->Output('I', 'ticket_' . $cod . '.pdf');
