<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

    // Limpiar búfer para evitar corrupción
    if (ob_get_length()) ob_end_clean();

    try {
        $db = getDB();
        // Obtener productos con imagen asignada
        $stmt = $db->prepare("SELECT codigop, image_path FROM productos WHERE image_path IS NOT NULL AND image_path != ''");
        $stmt->execute();
        $productos = $stmt->fetchAll();

        if (empty($productos)) {
            die("No hay productos con imágenes para respaldar.");
        }

        $zipName = "Respaldo_Fotos_ElRebusque_" . date('Ymd_His') . ".zip";
        $tempFile = tempnam(sys_get_temp_dir(), 'rebusque_zip');
        
        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            die("No se pudo crear el archivo ZIP temporal.");
        }

        $count = 0;
        $projectRoot = realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR;

        foreach ($productos as $p) {
            // La ruta en DB suele ser 'assets/images/products/archivo.jpg'
            // El projectRoot ya apunta a la raíz del proyecto web.
            $fullPath = $projectRoot . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $p['image_path']);
            
            if (file_exists($fullPath) && !is_dir($fullPath)) {
                $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
                $zipFriendlyName = $p['codigop'] . '.' . $ext;
                
                $zip->addFile($fullPath, $zipFriendlyName);
                $count++;
            }
        }

        $zip->close();

        if ($count === 0) {
            @unlink($tempFile);
            die("No se pudieron encontrar físicamente las imágenes de los productos en las rutas especificadas.");
        }

        // Configurar descarga
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipName . '"');
        header('Content-Length: ' . filesize($tempFile));
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($tempFile);
        @unlink($tempFile);
        exit;
    } catch (Exception $e) {
        die("Error crítico en exportación de fotos: " . $e->getMessage());
    }
?>
