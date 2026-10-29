CREATE TABLE IF NOT EXISTS producto_destacado (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    producto_id INT NULL,
    descripcion TEXT NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 0,
    fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_producto_destacado_producto (producto_id),
    CONSTRAINT fk_producto_destacado_producto FOREIGN KEY (producto_id)
        REFERENCES productos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO producto_destacado (id, producto_id, descripcion, activo)
VALUES (1, NULL, '', 0)
ON DUPLICATE KEY UPDATE id = VALUES(id);
