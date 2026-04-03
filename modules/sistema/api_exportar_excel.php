<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    $db = getDB();
    
    // Consulta de productos (solo las columnas principales para compatibilidad desktop)
    $stmt = $db->prepare("SELECT codigop, referencia, exisact, pventa, image_path FROM productos ORDER BY codigop ASC");
    $stmt->execute();
    $productos = $stmt->fetchAll();

    // Crear la hoja de cálculo
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Inventario Rebusque');

    // Estilo de encabezados
    $headers = ["Código", "Referencia", "Existencia", "Precio en Dólares", "Imagen"];
    $columnLetter = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($columnLetter . '1', $header);
        $sheet->getStyle($columnLetter . '1')->getFont()->setBold(true);
        $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        $columnLetter++;
    }

    // Llenar datos
    $rowId = 2;
    foreach ($productos as $p) {
        $sheet->setCellValue('A' . $rowId, $p['codigop']);
        $sheet->setCellValue('B' . $rowId, $p['referencia']);
        $sheet->setCellValue('C' . $rowId, (float)$p['exisact']);
        $sheet->setCellValue('D' . $rowId, (float)$p['pventa']);
        $sheet->setCellValue('E' . $rowId, $p['image_path']);
        $rowId++;
    }

    // Limpiar cualquier búfer de salida para evitar corrupción
    if (ob_get_length()) ob_end_clean();

    // Configurar descarga
    $filename = "BaseDatos_Rebusque_WEB_" . date('Ymd_His') . ".xlsx";
    
    $writer = new Xlsx($spreadsheet);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Expires: Fri, 01 Jan 1990 00:00:00 GMT'); // Fecha pasada
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Pragma: public');
    
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    die("Error crítico al generar el Excel: " . $e->getMessage());
}
