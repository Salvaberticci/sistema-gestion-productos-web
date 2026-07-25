<?php
require_once __DIR__ . '/includes/auth.php';
if (!isLoggedIn() || !isAdmin()) die("Acceso denegado");

$db = getDB();
echo "<h1>Reparación de Base de Datos</h1><pre>";

$fixes = [
    "ALTER TABLE historial_busquedas MODIFY producto_cod VARCHAR(50) NULL",
    "ALTER TABLE historial_busquedas ADD COLUMN IF NOT EXISTS termino_busqueda VARCHAR(255) DEFAULT '' AFTER producto_cod",
];

foreach ($fixes as $sql) {
    try {
        $db->exec($sql);
        echo "✓ $sql\n";
    } catch (Exception $e) {
        echo "ℹ {$e->getMessage()}\n";
    }
}

echo "\n✅ Reparación completada. <a href='modules/inventario/index.php'>Volver al inventario</a></pre>";
