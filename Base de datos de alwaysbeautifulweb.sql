-- Esquema de Always Beautiful para MySQL 8+ y MariaDB 10.5+.
-- Se puede ejecutar completo en MySQL Workbench o importar desde PHP.
-- No elimina datos ni contiene DELIMITER/procedimientos almacenados.

CREATE DATABASE IF NOT EXISTS alwaysbeautifuldb;
USE alwaysbeautifuldb;

-- Cuentas de clientes y administradores.
CREATE TABLE IF NOT EXISTS usuarios (
    id INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    fecha_nacimiento DATE NULL,
    genero ENUM('hombre', 'mujer') NULL,
    codigo_recuperacion VARCHAR(6) NULL,
    codigo_expira DATETIME NULL,
    rol ENUM('usuario', 'admin') NOT NULL DEFAULT 'usuario',
    foto_perfil VARCHAR(255) NULL,
    carrito_json LONGTEXT NULL,
    historial_json LONGTEXT NULL,
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_correo (correo)
) ENGINE=InnoDB;

-- Catálogo central usado por el sitio y el panel administrativo.
CREATE TABLE IF NOT EXISTS productos (
    id INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    precio INT UNSIGNED NOT NULL,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    categoria VARCHAR(50) NOT NULL DEFAULT 'Piel',
    descripcion TEXT NULL,
    imagen VARCHAR(255) NULL DEFAULT 'IMG/default.jpg',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    administrador_id INT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_productos_nombre (nombre),
    KEY idx_productos_administrador (administrador_id),
    CONSTRAINT fk_productos_administrador FOREIGN KEY (administrador_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Carrito relacional persistente por usuario y producto.
CREATE TABLE IF NOT EXISTS carrito (
    id INT NOT NULL AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT UNSIGNED NOT NULL DEFAULT 1,
    precio_unitario INT UNSIGNED NOT NULL,
    fecha_agregado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_carrito_usuario_producto (usuario_id, producto_id),
    KEY idx_carrito_producto (producto_id),
    CONSTRAINT fk_carrito_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_carrito_producto FOREIGN KEY (producto_id)
        REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Encabezado de cada compra confirmada.
CREATE TABLE IF NOT EXISTS pedidos (
    id INT NOT NULL AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    total INT UNSIGNED NOT NULL,
    estado VARCHAR(50) NOT NULL DEFAULT 'pendiente',
    referencia_pago VARCHAR(100) NULL,
    transaccion_id VARCHAR(100) NULL,
    metodo_pago VARCHAR(40) NULL,
    fecha_pedido TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pedidos_referencia (referencia_pago),
    UNIQUE KEY uq_pedidos_transaccion (transaccion_id),
    KEY idx_pedidos_usuario (usuario_id),
    CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- Detalle conserva nombre y precio históricos aunque luego se retire el producto.
CREATE TABLE IF NOT EXISTS detalles_pedido (
    id INT NOT NULL AUTO_INCREMENT,
    pedido_id INT NOT NULL,
    producto_id INT NULL,
    producto_nombre VARCHAR(150) NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    precio_unitario INT UNSIGNED NOT NULL,
    subtotal INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY idx_detalles_pedido (pedido_id),
    KEY idx_detalles_producto (producto_id),
    CONSTRAINT fk_detalles_pedido_pedido FOREIGN KEY (pedido_id)
        REFERENCES pedidos(id) ON DELETE CASCADE,
    CONSTRAINT fk_detalles_pedido_producto FOREIGN KEY (producto_id)
        REFERENCES productos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS historial_actividades (
    id INT NOT NULL AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    accion VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    detalles JSON NULL,
    ip_address VARCHAR(45) NULL,
    fecha_accion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_actividad_usuario (usuario_id),
    KEY idx_actividad_fecha (fecha_accion),
    CONSTRAINT fk_actividad_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS promociones (
    id INT NOT NULL AUTO_INCREMENT,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    categoria VARCHAR(50) NOT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0,
    imagen VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    administrador_id INT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_promociones_administrador (administrador_id),
    CONSTRAINT fk_promociones_administrador FOREIGN KEY (administrador_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS combos (
    id INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0,
    imagen VARCHAR(255) NULL,
    productos TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    administrador_id INT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_combos_administrador (administrador_id),
    CONSTRAINT fk_combos_administrador FOREIGN KEY (administrador_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Relación muchos-a-muchos entre combos y productos.
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

CREATE TABLE IF NOT EXISTS mensajes_contacto (
    id INT NOT NULL AUTO_INCREMENT,
    usuario_id INT NULL,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL,
    asunto VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    estado ENUM('no_leido', 'leido', 'respondido') NOT NULL DEFAULT 'no_leido',
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    foto_mensaje VARCHAR(255) NULL,
    PRIMARY KEY (id),
    KEY idx_mensajes_estado (estado),
    KEY idx_mensajes_fecha (fecha_creacion),
    KEY idx_mensajes_usuario (usuario_id),
    CONSTRAINT fk_mensajes_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS respuestas_mensajes (
    id INT NOT NULL AUTO_INCREMENT,
    mensaje_id INT NOT NULL,
    admin_id INT NOT NULL,
    respuesta TEXT NOT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_respuestas_admin (admin_id),
    CONSTRAINT fk_respuestas_mensaje FOREIGN KEY (mensaje_id)
        REFERENCES mensajes_contacto(id) ON DELETE CASCADE,
    CONSTRAINT fk_respuestas_admin FOREIGN KEY (admin_id)
        REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS galeria_inicio (
    id INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    ruta VARCHAR(255) NOT NULL,
    administrador_id INT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_galeria_administrador (administrador_id),
    CONSTRAINT fk_galeria_administrador FOREIGN KEY (administrador_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sesiones_inicio (
    id INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('slider', 'collage') NOT NULL DEFAULT 'slider',
    estilo_slider TINYINT NOT NULL DEFAULT 1,
    imagenes TEXT NULL,
    orden INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    administrador_id INT NULL,
    fecha_actualizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sesiones_administrador (administrador_id),
    CONSTRAINT fk_sesiones_administrador FOREIGN KEY (administrador_id)
        REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sesiones_imagenes (
    sesion_id INT NOT NULL,
    galeria_id INT NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    PRIMARY KEY (sesion_id, galeria_id),
    UNIQUE KEY uq_sesion_imagen_orden (sesion_id, orden),
    KEY idx_sesion_imagen_galeria (galeria_id),
    CONSTRAINT fk_sesion_imagen_sesion FOREIGN KEY (sesion_id)
        REFERENCES sesiones_inicio(id) ON DELETE CASCADE,
    CONSTRAINT fk_sesion_imagen_galeria FOREIGN KEY (galeria_id)
        REFERENCES galeria_inicio(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reseñas_productos (
    id INT NOT NULL AUTO_INCREMENT,
    usuario_id INT NULL,
    producto_id INT NULL,
    nombre_autor VARCHAR(100) NULL,
    producto_nombre VARCHAR(150) NOT NULL,
    estrellas TINYINT UNSIGNED NOT NULL,
    comentario TEXT NOT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_resena_usuario_producto (usuario_id, producto_nombre),
    KEY idx_resenas_producto (producto_id),
    CONSTRAINT fk_resenas_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_resenas_producto FOREIGN KEY (producto_id)
        REFERENCES productos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS votos_reseñas (
    id INT NOT NULL AUTO_INCREMENT,
    reseña_id INT NOT NULL,
    usuario_id INT NOT NULL,
    voto ENUM('like', 'dislike') NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_voto_usuario_resena (reseña_id, usuario_id),
    KEY idx_votos_usuario (usuario_id),
    CONSTRAINT fk_votos_resena FOREIGN KEY (reseña_id)
        REFERENCES reseñas_productos(id) ON DELETE CASCADE,
    CONSTRAINT fk_votos_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reportes_reseñas (
    id INT NOT NULL AUTO_INCREMENT,
    reseña_id INT NOT NULL,
    usuario_id INT NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reporte_usuario_resena (reseña_id, usuario_id),
    KEY idx_reportes_usuario (usuario_id),
    CONSTRAINT fk_reportes_resena FOREIGN KEY (reseña_id)
        REFERENCES reseñas_productos(id) ON DELETE CASCADE,
    CONSTRAINT fk_reportes_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Secciones iniciales de la página, sin duplicarlas al ejecutar el archivo otra vez.
INSERT INTO sesiones_inicio (nombre, tipo, estilo_slider, orden)
SELECT 'Slider principal', 'slider', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM sesiones_inicio WHERE nombre = 'Slider principal');

INSERT INTO sesiones_inicio (nombre, tipo, estilo_slider, orden)
SELECT 'Favoritos de la temporada', 'slider', 2, 2
WHERE NOT EXISTS (SELECT 1 FROM sesiones_inicio WHERE nombre = 'Favoritos de la temporada');

INSERT INTO sesiones_inicio (nombre, tipo, estilo_slider, orden)
SELECT 'Ofertas para ti', 'slider', 3, 3
WHERE NOT EXISTS (SELECT 1 FROM sesiones_inicio WHERE nombre = 'Ofertas para ti');
