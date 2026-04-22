CREATE DATABASE IF NOT EXISTS AlwaysBeautifulDB;
USE AlwaysBeautifulDB;

-- TABLA DE USUARIOS
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    rol VARCHAR(20) DEFAULT 'usuario',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE
);

-- TABLA DE PRODUCTOS
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    precio INT NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    categoria VARCHAR(50),
    descripcion TEXT,
    imagen VARCHAR(255),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TABLA DE CARRITO
CREATE TABLE IF NOT EXISTS carrito (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario INT NOT NULL,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- TABLA DE PEDIDOS
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    total INT NOT NULL,
    estado VARCHAR(50) DEFAULT 'pendiente',
    fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- TABLA DE DETALLES DE PEDIDOS
CREATE TABLE IF NOT EXISTS detalles_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario INT NOT NULL,
    subtotal INT NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- TABLA DE HISTORIAL DE ACTIVIDADES DEL USUARIO
CREATE TABLE IF NOT EXISTS historial_actividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    accion VARCHAR(100) NOT NULL,
    descripcion TEXT,
    detalles JSON,
    ip_address VARCHAR(45),
    fecha_accion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha_accion)
);

-- INSERTAR DATOS INICIALES DE USUARIOS
INSERT INTO usuarios (nombre, correo, contrasena, rol) VALUES
('Admin', 'admin@gmail.com', '123456', 'admin'),
('Usuario Demo', 'user@gmail.com', '123456', 'usuario');

-- INSERTAR DATOS INICIALES DE PRODUCTOS (36 productos totales)

-- MAQUILLAJE (9 productos)
INSERT INTO productos (nombre, precio, stock, categoria, descripcion) VALUES
('Paleta de sombras', 90000, 20, 'Maquillaje', 'Paleta de sombras profesional con 12 colores'),
('Gloss Sheglam', 10000, 30, 'Maquillaje', 'Gloss labial brillante y duradero'),
('Base Nars', 200000, 15, 'Maquillaje', 'Base de maquillaje de alta calidad'),
('Brochas', 30000, 25, 'Maquillaje', 'Set de 10 brochas para maquillaje'),
('Encrespador', 5000, 40, 'Maquillaje', 'Rizador de pestañas profesional'),
('Contorno', 80000, 18, 'Maquillaje', 'Contorno en polvo para definir rostro'),
('Pestañina', 50000, 22, 'Maquillaje', 'Máscara de pestañas voluminosa'),
('Lapices para labios', 5000, 35, 'Maquillaje', 'Lápiz delineador para labios'),
('Delineador', 12000, 28, 'Maquillaje', 'Delineador líquido de larga duración');

-- CABELLO (9 productos)
INSERT INTO productos (nombre, precio, stock, categoria, descripcion) VALUES
('Shampo y Condicionador', 40000, 25, 'Cabello', 'Shampo y acondicionador en set'),
('Protector térmico', 25000, 20, 'Cabello', 'Spray protector para secador'),
('Mascarilla para cabello', 32000, 18, 'Cabello', 'Mascarilla reparadora intensiva'),
('Masajeador de cuero cabelludo', 15000, 30, 'Cabello', 'Masajeador eléctrico para cuero cabelludo'),
('Jabón para cabello', 8000, 40, 'Cabello', 'Jabón natural para cabello'),
('Enruladores', 15000, 25, 'Cabello', 'Set de enruladores térmicos'),
('Crema skala', 30000, 22, 'Cabello', 'Crema para peinar'),
('Aceite para cabello', 25000, 28, 'Cabello', 'Aceite nutritivo para cabello'),
('Aceite de coco', 40000, 20, 'Cabello', 'Aceite de coco puro');

-- PIEL (9 productos)
INSERT INTO productos (nombre, precio, stock, categoria, descripcion) VALUES
('Vaseline', 10000, 50, 'Piel', 'Vaselina pura para labios y piel'),
('Protector solar', 30000, 25, 'Piel', 'Protector solar SPF 50'),
('Mascarilla para los labios', 30000, 20, 'Piel', 'Mascarilla hidratante para labios'),
('Mascarilla para la cara', 10000, 35, 'Piel', 'Mascarilla purificante facial'),
('Hidratante Aloe', 20000, 30, 'Piel', 'Crema hidratante con aloe vera'),
('Crema Ponds', 25000, 28, 'Piel', 'Crema facial Ponds'),
('Crema Nivea', 15000, 40, 'Piel', 'Crema hidratante Nivea'),
('Crema CeraVe', 40000, 18, 'Piel', 'Crema CeraVe profesional'),
('Agua micelar', 15000, 32, 'Piel', 'Agua micelar desmaquillante');

-- ACCESORIOS (9 productos)
INSERT INTO productos (nombre, precio, stock, categoria, descripcion) VALUES
('Moño', 5000, 50, 'Accesorios', 'Moño elástico para cabello'),
('Pinzas', 6000, 45, 'Accesorios', 'Set de pinzas para cabello'),
('Gorro de dormir', 40000, 22, 'Accesorios', 'Gorro satinado de dormir'),
('Aretes', 5000, 60, 'Accesorios', 'Aretes acero inoxidable'),
('Caiman de cabello', 15000, 35, 'Accesorios', 'Clip caimán para cabello'),
('Pestañas postizas', 12000, 40, 'Accesorios', 'Pestañas postizas reutilizables'),
('Uñas postizas', 10000, 38, 'Accesorios', 'Juego de uñas postizas'),
('Collar', 30000, 20, 'Accesorios', 'Collar de acero inoxidable'),
('Antifaz para dormir', 20000, 25, 'Accesorios', 'Antifaz de seda para dormir');
