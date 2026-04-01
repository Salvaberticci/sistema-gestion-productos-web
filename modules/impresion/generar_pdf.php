?php
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

// Configuración de ticket de 57mm (aprox 2.24 pulgadas)
// FPDF utiliza mm por defecto
$width = 57;
$height = 100; // Altura dinámica o fija? Usaremos 80-100 para un ticket estándar

// Crear PDF con tamaño personalizado
$pdf = new FPDF('P', 'mm', [$width, $height]);
$pdf->SetMargins(2, 5, 2);
$pdf->AddPage();
$pdf->SetAutoPageBreak(false);

// Logo (si existe)
$logo_path = __DIR__ . '/../../assets/images/logo.png';
if (file_exists($logo_path)) {
    $pdf->Image($logo_path, ($width - 30) / 2, 5, 30);
    $pdf->Ln(25); // Espacio después del logo
} else {
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'EL REBUSQUE', 0, 1, 'C');
}

$pdf->SetFont('Arial', '', 8);
$pdf->Cell(0, 4, date('d/m/Y H:i'), 0, 1, 'C');
$pdf->Ln(2);

// Separador
$pdf->Cell(0, 0, '', 'T');
$pdf->Ln(3);

// Nombre del producto con auto-escalado
$name = utf8_decode($p['referencia']);
$fsize = 12;
if (strlen($name) > 20) $fsize = 10;
if (strlen($name) > 25) $fsize = 8;
if (strlen($name) > 35) $fsize = 7;

$pdf->SetFont('Arial', 'B', $fsize);
$pdf->MultiCell(0, 5, $name, 0, 'C');
$pdf->Ln(3);

// Precio en Bolívares (Grande)
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 8, "Bs. " . number_format($price_bs, 2, '.', ','), 0, 1, 'C');

// Ref USD (Pequeño)
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 5, "Ref: " . formatCurrency((float)$p['pventa']), 0, 1, 'C');

$pdf->Ln(3);
$pdf->Cell(0, 0, '', 'T');
$pdf->Ln(2);

// Footer
$pdf->SetFont('Arial', 'I', 7);
$pdf->MultiCell(0, 3, "Gracias por su compra\nValera, Edo. Trujillo", 0, 'C');

// Output
$pdf->Output('I', 'ticket_' . $cod . '.pdf');
