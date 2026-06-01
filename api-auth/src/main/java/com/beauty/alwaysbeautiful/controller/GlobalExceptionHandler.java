package com.beauty.alwaysbeautiful.controller;

// Manejador global de excepciones.
// Captura los errores de validación Bean Validation y los transforma
// en respuestas JSON claras y consistentes para el cliente.

import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.validation.FieldError;
import org.springframework.web.bind.MethodArgumentNotValidException;
import org.springframework.web.bind.annotation.ExceptionHandler;
import org.springframework.web.bind.annotation.RestControllerAdvice;

import java.util.HashMap;
import java.util.Map;

/**
 * GlobalExceptionHandler: intercepta excepciones lanzadas en los controladores
 * y devuelve respuestas HTTP con formato JSON uniforme.
 *
 * Sin este manejador, Spring devolvería trazas de error internas al cliente.
 */
@RestControllerAdvice
public class GlobalExceptionHandler {

    /**
     * Maneja los errores de validación de campos (@NotBlank, @Email, etc.).
     * Se dispara cuando @Valid detecta un campo inválido en el RequestBody.
     *
     * Respuesta ejemplo:
     * HTTP 400 Bad Request
     * {
     *   "exito": false,
     *   "mensaje": "Error de validación en los datos enviados",
     *   "errores": {
     *     "correo": "Ingrese un correo válido",
     *     "contrasena": "La contraseña debe tener al menos 6 caracteres"
     *   }
     * }
     */
    @ExceptionHandler(MethodArgumentNotValidException.class)
    public ResponseEntity<Map<String, Object>> manejarValidacion(
            MethodArgumentNotValidException ex) {

        // Recolectar todos los errores de campo en un mapa { campo: mensaje }
        Map<String, String> erroresCampos = new HashMap<>();
        ex.getBindingResult().getAllErrors().forEach(error -> {
            String campo   = ((FieldError) error).getField();
            String mensaje = error.getDefaultMessage();
            erroresCampos.put(campo, mensaje);
        });

        // Construir respuesta estructurada
        Map<String, Object> respuesta = new HashMap<>();
        respuesta.put("exito",   false);
        respuesta.put("mensaje", "Error de validación en los datos enviados");
        respuesta.put("errores", erroresCampos);

        // HTTP 400: petición mal formada
        return ResponseEntity.status(HttpStatus.BAD_REQUEST).body(respuesta);
    }

    /**
     * Maneja cualquier excepción no controlada (errores inesperados del servidor).
     * Evita exponer detalles internos del sistema al cliente.
     */
    @ExceptionHandler(Exception.class)
    public ResponseEntity<Map<String, Object>> manejarErrorGeneral(Exception ex) {

        Map<String, Object> respuesta = new HashMap<>();
        respuesta.put("exito",   false);
        respuesta.put("mensaje", "Error interno del servidor. Intente de nuevo más tarde.");

        // HTTP 500: error interno del servidor
        return ResponseEntity.status(HttpStatus.INTERNAL_SERVER_ERROR).body(respuesta);
    }
}
