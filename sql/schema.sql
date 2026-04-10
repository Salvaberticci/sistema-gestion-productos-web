-- =============================================
-- Sistema El Rebusque - Web (PHP/MariaDB)
-- Schema v1.0
-- =============================================

CREATE DATABASE IF NOT EXISTS el_rebusque_web
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE el_rebusque_web;

-- Tabla de Usuarios con Roles
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    rol ENUM('admin', 'empleado') NOT NULL DEFAULT 'empleado',
    activo TINYINT(1) DEFAULT 1,
    consultas_realizadas INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de Productos
CREATE TABLE IF NOT EXISTS productos (
    codigop VARCHAR(50) PRIMARY KEY,
    referencia VARCHAR(255),
    exisact DECIMAL(10,2) DEFAULT 0,
    pcosto DECIMAL(10,2) DEFAULT 0,
    pventa DECIMAL(10,2) DEFAULT 0,
    precio_almacen DECIMAL(10,2) DEFAULT 0,
    precio_venta DECIMAL(10,2) DEFAULT 0,
    rebusque DECIMAL(10,2) DEFAULT 0,
    image_path VARCHAR(255) DEFAULT NULL,
    busquedas INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Historial detallado de búsquedas (para analítica diaria)
CREATE TABLE IF NOT EXISTS historial_busquedas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    producto_cod VARCHAR(50) NOT NULL,
    fecha_busqueda TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (producto_cod) REFERENCES productos(codigop) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de Configuracion
CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) UNIQUE NOT NULL,
    valor TEXT
) ENGINE=InnoDB;

-- Datos iniciales
INSERT INTO configuracion (clave, valor) VALUES ('tasa_cambio', '1.0')
ON DUPLICATE KEY UPDATE valor = valor;

-- Usuario admin por defecto (password: 12345)
INSERT INTO usuarios (username, password, nombre_completo, rol)
VALUES ('admin', '$2y$10$8K1p/a0Hk1xOz7JB5XJXHOxSqGkNhF1qOJQ8kN5tQdKjYGgLJHxXi', 'Administrador', 'admin')
ON DUPLICATE KEY UPDATE username = username;

-- =============================================
-- Tablas del Módulo de Ventas
-- =============================================

CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cedula VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ordenes_venta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    cliente_id INT NOT NULL,
    estado ENUM('pendiente', 'completado', 'rechazado') NOT NULL DEFAULT 'pendiente',
    total_usd DECIMAL(10,2) DEFAULT 0,
    total_bs DECIMAL(10,2) DEFAULT 0,
    tasa_aplicada DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (cliente_id) REFERENCES clientes(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ordenes_detalles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orden_id INT NOT NULL,
    producto_cod VARCHAR(50) NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    precio_unitario_usd DECIMAL(10,2) NOT NULL,
    subtotal_usd DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (orden_id) REFERENCES ordenes_venta(id),
    FOREIGN KEY (producto_cod) REFERENCES productos(codigop) ON UPDATE CASCADE
) ENGINE=InnoDB;
