-- Migracion opcional para una base creada con el esquema antiguo del proyecto.
-- Haga primero una copia de seguridad. Ejecute una sola vez.
-- No usar si las columnas/indices ya existen: en ese caso la pagina ya tiene
-- el esquema actualizado y se debe ejecutar solo Base de datos de alwaysbeautifulweb.sql.
USE alwaysbeautifuldb;

-- Prevalidacion: ambas consultas deben devolver cero filas.
SELECT nombre, COUNT(*) AS cantidad
FROM productos
GROUP BY nombre
HAVING COUNT(*) > 1;

SELECT usuario_id, producto_id, COUNT(*) AS cantidad
FROM carrito
GROUP BY usuario_id, producto_id
HAVING COUNT(*) > 1;

CREATE TABLE IF NOT EXISTS combos (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0,
    imagen VARCHAR(255) NULL,
    productos TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    administrador_id INT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_combos_administrador FOREIGN KEY (administrador_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reseñas_productos (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    nombre_autor VARCHAR(100) NULL,
    producto_nombre VARCHAR(150) NOT NULL,
    estrellas TINYINT UNSIGNED NOT NULL,
    comentario TEXT NOT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_resena_usuario_producto (usuario_id, producto_nombre),
    CONSTRAINT fk_resenas_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Normalizar identificadores que el codigo de la pagina usa al buscar productos.
ALTER TABLE productos
    ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN administrador_id INT NULL,
    ADD UNIQUE KEY uq_productos_nombre (nombre),
    ADD CONSTRAINT fk_productos_administrador FOREIGN KEY (administrador_id)
        REFERENCES usuarios(id) ON DELETE SET NULL;

ALTER TABLE carrito
    MODIFY COLUMN cantidad INT UNSIGNED NOT NULL DEFAULT 1,
    ADD UNIQUE KEY uq_carrito_usuario_producto (usuario_id, producto_id);

-- Vincular reseñas existentes con el producto por su nombre histórico.
ALTER TABLE reseñas_productos
    ADD COLUMN producto_id INT NULL AFTER usuario_id,
    ADD KEY idx_resenas_producto (producto_id),
    ADD CONSTRAINT fk_resenas_producto FOREIGN KEY (producto_id)
        REFERENCES productos(id) ON DELETE SET NULL;

UPDATE reseñas_productos AS r
JOIN productos AS p
    ON p.nombre = r.producto_nombre
SET r.producto_id = p.id
WHERE r.producto_id IS NULL;

-- Guardar datos de trazabilidad de la transacción de pago.
ALTER TABLE pedidos
    ADD COLUMN referencia_pago VARCHAR(100) NULL AFTER estado,
    ADD COLUMN transaccion_id VARCHAR(100) NULL AFTER referencia_pago,
    ADD COLUMN metodo_pago VARCHAR(40) NULL AFTER transaccion_id,
    ADD UNIQUE KEY uq_pedidos_referencia (referencia_pago),
    ADD UNIQUE KEY uq_pedidos_transaccion (transaccion_id);

-- Conservar el nombre histórico aunque el producto sea retirado del catálogo.
ALTER TABLE detalles_pedido
    ADD COLUMN producto_nombre VARCHAR(150) NULL AFTER producto_id,
    MODIFY COLUMN producto_id INT NULL;

UPDATE detalles_pedido AS d
LEFT JOIN productos AS p ON p.id = d.producto_id
SET d.producto_nombre = COALESCE(p.nombre, CONCAT('Producto #', d.producto_id))
WHERE d.producto_nombre IS NULL OR d.producto_nombre = '';

ALTER TABLE detalles_pedido
    MODIFY COLUMN producto_nombre VARCHAR(150) NOT NULL,
    DROP FOREIGN KEY detalles_pedido_ibfk_2,
    ADD CONSTRAINT fk_detalles_pedido_producto FOREIGN KEY (producto_id)
        REFERENCES productos(id) ON DELETE SET NULL;

-- Relacion muchos-a-muchos entre combos y productos.
CREATE TABLE IF NOT EXISTS combo_productos (
    combo_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (combo_id, producto_id),
    KEY idx_combo_productos_producto (producto_id),
    CONSTRAINT fk_combo_productos_combo FOREIGN KEY (combo_id)
        REFERENCES combos(id) ON DELETE CASCADE,
    CONSTRAINT fk_combo_productos_producto FOREIGN KEY (producto_id)
        REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB;
