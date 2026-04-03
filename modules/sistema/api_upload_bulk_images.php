<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['fotos'])) {
    header('Location: index.php?status=error&msg=Solicitud+inválida');
    exit;
}

$files = $_FILES['fotos'];
$totalFiles = count($files['name']);
$linked = 0;
$skipped = 0;
$errors = 0;

$targetDir = realpath(__DIR__ . '/../../assets/images/products/') . DIRECTORY_SEPARATOR;
$db = getDB();

$allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

for ($i = 0; $i < $totalFiles; $i++) {
    if ($files['error'][$i] !== UPLOAD_ERR_OK) {
        $errors++;
        continue;
    }

    $fileName = $files['name'][$i];
    $tmpName = $files['tmp_name'][$i];
    
    $pathInfo = pathinfo($fileName);
    $ext = strtolower($pathInfo['extension'] ?? '');
    $codigop = trim($pathInfo['filename']); // El nombre del archivo es el código de barras

    if (!in_array($ext, $allowedExts)) {
        $skipped++;
        continue;
    }

    try {
        // Verificar si el producto existe
        $stmt = $db->prepare("SELECT codigop FROM productos WHERE codigop = ?");
        $stmt->execute([$codigop]);
        $product = $stmt->fetch();

        if ($product) {
            $finalFileName = $codigop . '.' . $ext;
            $destPath = $targetDir . $finalFileName;
            
            if (move_uploaded_file($tmpName, $destPath)) {
                // Actualizar DB
                $relPath = 'assets/images/products/' . $finalFileName;
                $upd = $db->prepare("UPDATE productos SET image_path = ? WHERE codigop = ?");
                $upd->execute([$relPath, $codigop]);
                $linked++;
            } else {
                $errors++;
            }
        } else {
            $skipped++;
        }
    } catch (Exception $e) {
        $errors++;
    }
}

$statusMsg = "Proceso de imágenes finalizado.";
$detailMsg = "📸 Vinculadas: $linked | ⏭️ Ignoradas (no existe el código o formato inválido): $skipped";
if ($errors > 0) $detailMsg .= " | ⚠️ Fallos técnicos: $errors";

header("Location: index.php?status=success&msg=" . urlencode($statusMsg) . "&detail=" . urlencode($detailMsg));
exit;
