<?php
require_once __DIR__ . '/includes/auth.php';
if (!isLoggedIn() || !isAdmin()) die("Acceso denegado");

$db = getDB();
echo "<h1>Reparación de Base de Datos</h1><pre>";

$fixes = [
    "ALTER TABLE historial_busquedas MODIFY producto_cod VARCHAR(50) NULL",
    "ALTER TABLE historial_busquedas ADD COLUMN IF NOT EXISTS termino_busqueda VARCHAR(255) DEFAULT '' AFTER producto_cod",
];

// Detectar y reparar FK de ordenes_detalles para agregar ON DELETE CASCADE
try {
    $fk = $db->query("
        SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ordenes_detalles'
        AND COLUMN_NAME = 'producto_cod' AND REFERENCED_TABLE_NAME = 'productos'
    ")->fetchColumn();
    if ($fk) {
        $db->exec("ALTER TABLE ordenes_detalles DROP FOREIGN KEY $fk");
        $db->exec("ALTER TABLE ordenes_detalles ADD CONSTRAINT ordenes_detalles_productos_fk
                   FOREIGN KEY (producto_cod) REFERENCES productos(codigop)
                   ON UPDATE CASCADE ON DELETE CASCADE");
        echo "✓ FK ordenes_detalles actualizada con ON DELETE CASCADE\n";
    }
} catch (Exception $e) {
    echo "ℹ {$e->getMessage()}\n";
}

foreach ($fixes as $sql) {
    try {
        $db->exec($sql);
        echo "✓ $sql\n";
    } catch (Exception $e) {
        echo "ℹ {$e->getMessage()}\n";
    }
}

echo "\n✅ Reparación completada. <a href='modules/inventario/index.php'>Volver al inventario</a></pre>";
