package com.beauty.alwaysbeautiful.controller;

// Controlador REST que expone los endpoints del servicio de autenticación.
// Recibe las peticiones HTTP, valida la entrada y delega al servicio.

import com.beauty.alwaysbeautiful.dto.AuthResponse;
import com.beauty.alwaysbeautiful.dto.LoginRequest;
import com.beauty.alwaysbeautiful.dto.RegistroRequest;
import com.beauty.alwaysbeautiful.service.AuthService;
import jakarta.validation.Valid;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

/**
 * AuthController: expone los endpoints públicos de autenticación.
 *
 * Base URL: /api/auth
 *
 * Endpoints disponibles:
 *  POST /api/auth/registro  → registrar un nuevo usuario
 *  POST /api/auth/login     → iniciar sesión con correo y contraseña
 */
@RestController                    // Indica que es un controlador REST (responde JSON)
@RequestMapping("/api/auth")       // Prefijo común para todos los endpoints de este controlador
@CrossOrigin(origins = "*")        // Permite peticiones desde cualquier origen (CORS para el front-end PHP/web)
public class AuthController {

    // Servicio que contiene la lógica de negocio de autenticación
    private final AuthService authService;

    // Inyección por constructor
    public AuthController(AuthService authService) {
        this.authService = authService;
    }

    // ─── POST /api/auth/registro ───────────────────────────────────────────────

    /**
     * Endpoint de Registro de Usuario.
     *
     * Recibe: JSON con { nombre, correo, contrasena }
     * Devuelve:
     *   - 201 Created + { exito: true, mensaje, usuario } si el registro es exitoso
     *   - 400 Bad Request + { exito: false, mensaje }    si el correo ya existe
     *   - 400 Bad Request + errores de validación         si los campos son inválidos
     *
     * Ejemplo de petición:
     * POST /api/auth/registro
     * Content-Type: application/json
     * {
     *   "nombre": "Ana García",
     *   "correo": "ana@mail.com",
     *   "contrasena": "miClave123"
     * }
     */
    @PostMapping("/registro")
    public ResponseEntity<AuthResponse> registro(
            @Valid @RequestBody RegistroRequest request) {  // @Valid activa Bean Validation

        // Delegar al servicio y obtener el resultado
        AuthResponse respuesta = authService.registrar(request);

        if (respuesta.isExito()) {
            // HTTP 201 Created: recurso creado exitosamente
            return ResponseEntity.status(HttpStatus.CREATED).body(respuesta);
        } else {
            // HTTP 400 Bad Request: no se pudo crear el usuario (correo duplicado)
            return ResponseEntity.status(HttpStatus.BAD_REQUEST).body(respuesta);
        }
    }

    // ─── POST /api/auth/login ──────────────────────────────────────────────────

    /**
     * Endpoint de Inicio de Sesión.
     *
     * Recibe: JSON con { correo, contrasena }
     * Devuelve:
     *   - 200 OK         + { exito: true,  mensaje: "Autenticación satisfactoria", usuario }
     *   - 401 Unauthorized + { exito: false, mensaje: "Error en la autenticación" }
     *
     * Ejemplo de petición:
     * POST /api/auth/login
     * Content-Type: application/json
     * {
     *   "correo": "ana@mail.com",
     *   "contrasena": "miClave123"
     * }
     */
    @PostMapping("/login")
    public ResponseEntity<AuthResponse> login(
            @Valid @RequestBody LoginRequest request) {  // @Valid activa Bean Validation

        // Delegar al servicio de autenticación
        AuthResponse respuesta = authService.login(request);

        if (respuesta.isExito()) {
            // HTTP 200 OK: autenticación satisfactoria
            return ResponseEntity.ok(respuesta);
        } else {
            // HTTP 401 Unauthorized: error en la autenticación
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED).body(respuesta);
        }
    }

    // ─── GET /api/auth/salud ──────────────────────────────────────────────────

    /**
     * Endpoint de salud (health-check).
     * Permite verificar que la API está activa sin necesidad de autenticarse.
     *
     * Devuelve: 200 OK + mensaje de estado
     */
    @GetMapping("/salud")
    public ResponseEntity<String> salud() {
        return ResponseEntity.ok("Always Beautiful API - Servicio de autenticación activo ✓");
    }
}
