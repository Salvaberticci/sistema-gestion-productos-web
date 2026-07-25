<?php
/**
 * Script de Reparación de Base de Datos - El Rebusque
 * Propósito: Permitir la edición de códigos de barras (cascada).
 */

require_once __DIR__ . '/includes/auth.php';

// Solo administradores pueden ejecutar esto
if (!isLoggedIn() || !isAdmin()) {
    die("No tienes permisos para ejecutar esta reparación.");
}

$db = getDB();

try {
    echo "<h1>🛠️ Modo Reparación: Sistema de Productos</h1>";
    
    // 1. Intentar identificar el nombre de la FK
    // El error 1451 reportó ordenes_detalles_ibfk_2, lo cual es estándar de InnoDB
    $fk_name = "ordenes_detalles_ibfk_2";
    
    // Si falla porque MariaDB le puso otro nombre, lo buscamos
    $stmt = $db->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'ordenes_detalles' 
          AND COLUMN_NAME = 'producto_cod' 
          AND REFERENCED_TABLE_NAME = 'productos'
    ");
    $db_fk_name = $stmt->fetchColumn();
    
    if ($db_fk_name) {
        $fk_name = $db_fk_name;
    }

    echo "<p>Identificando llave foránea: <b>$fk_name</b>...</p>";

    // Transacción para seguridad
    $db->beginTransaction();

    // 2. Eliminar la restricción actual
    $db->exec("ALTER TABLE ordenes_detalles DROP FOREIGN KEY $fk_name");
    echo "<p style='color:green;'>✓ Restricción antigua eliminada exitosamente.</p>";

    // 3. Crear la nueva con ON UPDATE CASCADE y ON DELETE CASCADE
    $db->exec("ALTER TABLE ordenes_detalles 
               ADD CONSTRAINT ordenes_detalles_productos_fk 
               FOREIGN KEY (producto_cod) 
               REFERENCES productos(codigop) 
               ON UPDATE CASCADE ON DELETE CASCADE");
    
    echo "<p style='color:green;'>✓ Nueva regla de 'Actualización en Cascada' aplicada correctamente.</p>";

    $db->commit();
    
    echo "<hr>";
    echo "<p style='font-size:1.2rem; color: #34d399;'><b>¡Éxito total!</b></p>";
    echo "<p>Ya puedes volver al Inventario y cambiar el Código de Barras de cualquier producto sin errores.</p>";
    echo "<p><a href='modules/inventario/index.php' style='display:inline-block; padding:10px 20px; background:#2563eb; color:#fff; text-decoration:none; border-radius:8px;'>Ir al Inventario agora</a></p>";

    // Auto-destrucción por seguridad (opcional, pero mejor dejarlo un momento)
    // rename(__FILE__, __FILE__ . '.done');

} catch (Exception $e) {
    if ($db->inTransaction()) { $db->rollBack(); }
    echo "<div style='background:#fee2e2; color:#991b1b; padding:15px; border-radius:8px; border:1px solid #f87171;'>";
    echo "<h3>Error en la reparación:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
