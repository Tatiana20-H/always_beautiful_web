package com.beauty.alwaysbeautiful;

// Punto de entrada principal de la aplicación Spring Boot
// Always Beautiful - API de Autenticación y Registro de Usuarios
// GA7-220501096-AA5-EV01

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;

/**
 * Clase principal que arranca el servidor Spring Boot.
 * Esta API expone los endpoints de registro e inicio de sesión
 * para el sistema web de Always Beautiful.
 */
@SpringBootApplication
public class AlwaysBeautifulApplication {

    public static void main(String[] args) {
        // Inicia el contenedor de Spring y levanta el servidor embebido (Tomcat)
        SpringApplication.run(AlwaysBeautifulApplication.class, args);
    }
}
