-- ─────────────────────────────────────────────────────────────────────────────
-- Script SQL: Tabla de usuarios para la API de Autenticación
-- Always Beautiful - GA7-220501096-AA5-EV01
-- Base de datos: AlwaysBeautifulDB
-- ─────────────────────────────────────────────────────────────────────────────

-- Usar la base de datos del proyecto
USE AlwaysBeautifulDB;

-- ─── Tabla: usuarios ─────────────────────────────────────────────────────────
-- Almacena las cuentas de usuario del sistema Always Beautiful.
-- La contraseña se guarda como hash BCrypt (nunca en texto plano).
CREATE TABLE IF NOT EXISTS usuarios (
    id          INT AUTO_INCREMENT PRIMARY KEY,   -- Identificador único autoincremental
    nombre      VARCHAR(100)  NOT NULL,           -- Nombre completo del usuario
    correo      VARCHAR(150)  NOT NULL UNIQUE,    -- Email único (actúa como nombre de usuario)
    contrasena  VARCHAR(255)  NOT NULL,           -- Hash BCrypt de la contraseña
    rol         VARCHAR(20)   NOT NULL DEFAULT 'CLIENTE',  -- Rol: CLIENTE o ADMIN
    creado_en   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP    -- Fecha de registro
);

-- ─── Datos de prueba ──────────────────────────────────────────────────────────
-- Contraseña "admin123" encriptada con BCrypt (factor 12)
-- NOTA: Cambiar estas credenciales en producción
INSERT IGNORE INTO usuarios (nombre, correo, contrasena, rol) VALUES
('Administrador',  'admin@alwaysbeautiful.com', '$2a$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQyCgfl7n3N.W3BUkRU3B9Qqa', 'ADMIN'),
('Usuario Demo',   'demo@alwaysbeautiful.com',  '$2a$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQyCgfl7n3N.W3BUkRU3B9Qqa', 'CLIENTE');
-- Contraseña para ambos registros de prueba: "admin123"
