ALTER TABLE historial_busquedas ADD COLUMN IF NOT EXISTS termino_busqueda VARCHAR(255) DEFAULT '' AFTER producto_cod;
ALTER TABLE historial_busquedas MODIFY producto_cod VARCHAR(50) NULL;
