<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['excel_file'])) {
    header('Location: index.php?status=error&msg=Solicitud+inválida');
    exit;
}

$file = $_FILES['excel_file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, ['xlsx', 'xls'])) {
    header('Location: index.php?status=error&msg=Formato+no+soportado.+Usa+.xlsx+o+.xls');
    exit;
}

try {
    $db = getDB();
    $spreadsheet = IOFactory::load($file['tmp_name']);
    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestRow();
    
    // Obtenemos los datos de la fila 1 para mapeo de columnas dinámico
    $cols = [];
    $highestCol = $sheet->getHighestColumn();
    $headerRow = $sheet->rangeToArray('A1:' . $highestCol . '1', NULL, TRUE, FALSE)[0];

    foreach ($headerRow as $index => $name) {
        if ($name) {
            $cleanName = mb_strtolower(trim($name), 'UTF-8');
            // Mapeo flexible
            if (strpos($cleanName, 'código') !== false || strpos($cleanName, 'codigo') !== false) $cols['cod'] = $index;
            if (strpos($cleanName, 'referencia') !== false) $cols['ref'] = $index;
            if (strpos($cleanName, 'existencia') !== false) $cols['exis'] = $index;
            if (strpos($cleanName, 'dólares') !== false || strpos($cleanName, 'dolares') !== false || strpos($cleanName, 'pventa') !== false) $cols['pv'] = $index;
            if (strpos($cleanName, 'imagen') !== false) $cols['img'] = $index;
        }
    }

    // Validación mínima: Código y Referencia son obligatorios
    if (!isset($cols['cod']) || !isset($cols['ref'])) {
        throw new Exception("No se encontraron las columnas requeridas (Código y Referencia). Verifique los encabezados.");
    }

    $processed = 0;
    $updated = 0;
    $created = 0;
    $errors = 0;

    // Obtener el índice real de las columnas basado en las letras (A, B, C...)
    // $cols tiene los índices 0, 1, 2... que corresponden a las letras
    $colMap = [
        'cod' => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cols['cod'] + 1),
        'ref' => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cols['ref'] + 1),
        'exis' => isset($cols['exis']) ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cols['exis'] + 1) : null,
        'pv' => isset($cols['pv']) ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cols['pv'] + 1) : null,
        'img' => isset($cols['img']) ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cols['img'] + 1) : null,
    ];

    for ($row = 2; $row <= $highestRow; $row++) {
        // Leer el código usando el valor formateado para evitar notación científica (ej. códigos de barras largos)
        $cod = trim($sheet->getCell($colMap['cod'] . $row)->getFormattedValue());
        
        if (empty($cod) || strtolower($cod) === 'nan') continue;

        $ref = trim($sheet->getCell($colMap['ref'] . $row)->getFormattedValue());
        
        $exis = 0;
        if ($colMap['exis']) {
            $valExis = $sheet->getCell($colMap['exis'] . $row)->getValue();
            $exis = (float)str_replace(',', '.', (string)$valExis);
        }
        
        $pv = 0;
        if ($colMap['pv']) {
            $valPv = $sheet->getCell($colMap['pv'] . $row)->getValue();
            $pv = (float)str_replace(',', '.', (string)$valPv);
        }
        
        $img = '';
        if ($colMap['img']) {
            $img = trim((string)$sheet->getCell($colMap['img'] . $row)->getValue());
        }

        try {
            // Smart Sync: Buscar si existe usando el código principal
            $stmt_check = $db->prepare("SELECT codigop, image_path FROM productos WHERE codigop = ?");
            $stmt_check->execute([$cod]);
            $existing = $stmt_check->fetch();

            if ($existing) {
                // Protección de Imagen
                $final_img = (!empty($img) && $img !== 'nan') ? $img : $existing['image_path'];
                
                $stmt_upd = $db->prepare("UPDATE productos SET referencia = ?, exisact = ?, pventa = ?, image_path = ? WHERE codigop = ?");
                $stmt_upd->execute([$ref, (float)$exis, (float)$pv, $final_img, $cod]);
                $updated++;
            } else {
                // Crear nuevo
                $stmt_ins = $db->prepare("INSERT INTO productos (codigop, referencia, exisact, pventa, image_path, pcosto) VALUES (?, ?, ?, ?, ?, 0)");
                $stmt_ins->execute([$cod, $ref, (float)$exis, (float)$pv, $img]);
                $created++;
            }
            $processed++;
        } catch (Exception $rowEx) {
            $errors++;
            if (!isset($firstError)) $firstError = $rowEx->getMessage();
        }
    }

    $statusMsg = $errors === 0 ? "Sincronización finalizada con éxito." : "Sincronización finalizada con advertencias/errores.";
    $detailMsg = "📊 Procesados: $processed | ✅ Creados: $created | 🔄 Actualizados: $updated";
    if ($errors > 0) {
        $detailMsg .= " | ⚠️ Errores: $errors";
        $detailMsg .= " | Primer Error: " . ($firstError ?? 'Desconocido');
    }

    header("Location: index.php?status=success&msg=" . urlencode($statusMsg) . "&detail=" . urlencode($detailMsg));
    exit;

} catch (Exception $e) {
    header("Location: index.php?status=error&msg=" . urlencode("Error en importación: " . $e->getMessage()));
    exit;
}
