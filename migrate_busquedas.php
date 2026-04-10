<?php
require_once __DIR__ . '/includes/auth.php'; // Usa db connection context

try {
    $db = getDB();
    $sql = "
    CREATE TABLE IF NOT EXISTS historial_busquedas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NULL,
        producto_cod VARCHAR(50) NOT NULL,
        fecha_busqueda TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
        FOREIGN KEY (producto_cod) REFERENCES productos(codigop) ON UPDATE CASCADE ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ";
    
    $db->exec($sql);
    echo "Tabla historial_busquedas creada o ya existente de manera exitosa.\n";
} catch (PDOException $e) {
    echo "Error al crear la tabla: " . $e->getMessage() . "\n";
}
