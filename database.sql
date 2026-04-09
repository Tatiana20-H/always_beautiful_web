CREATE DATABASE AlwaysBeautifulDB;
USE AlwaysBeautifulDB;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    correo VARCHAR(100),
    contrasena VARCHAR(100)
);

INSERT INTO Usuario(nombre,correo,password,rol) VALUES
('Admin','admin@gmail.com','123456','admin'),
('Usuario','user@gmail.com','123456','usuario');

SELECT * FROM Usuario
