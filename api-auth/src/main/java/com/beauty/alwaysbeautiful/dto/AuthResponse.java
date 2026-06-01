package com.beauty.alwaysbeautiful.dto;

// DTO de respuesta unificada para registro y login.
// Contiene el mensaje de estado, datos del usuario (sin contraseña) y código HTTP.

/**
 * AuthResponse: estructura de respuesta JSON que devuelve la API
 * en cualquier operación de autenticación.
 *
 * Ejemplo exitoso:
 * {
 *   "exito": true,
 *   "mensaje": "Autenticación satisfactoria",
 *   "usuario": { "id": 1, "nombre": "Ana", "correo": "ana@mail.com", "rol": "CLIENTE" }
 * }
 *
 * Ejemplo de error:
 * {
 *   "exito": false,
 *   "mensaje": "Error en la autenticación: credenciales incorrectas"
 * }
 */
public class AuthResponse {

    // Indica si la operación fue exitosa o no
    private boolean exito;

    // Mensaje descriptivo del resultado (éxito o tipo de error)
    private String mensaje;

    // Datos públicos del usuario (se omite la contraseña por seguridad)
    private UsuarioInfo usuario;

    // ─── Constructor de error (sin datos de usuario) ───────────────────────────
    public AuthResponse(boolean exito, String mensaje) {
        this.exito   = exito;
        this.mensaje = mensaje;
    }

    // ─── Constructor de éxito (incluye datos del usuario) ─────────────────────
    public AuthResponse(boolean exito, String mensaje, UsuarioInfo usuario) {
        this.exito   = exito;
        this.mensaje = mensaje;
        this.usuario = usuario;
    }

    // ─── Getters ───────────────────────────────────────────────────────────────
    public boolean isExito()              { return exito; }
    public String getMensaje()            { return mensaje; }
    public UsuarioInfo getUsuario()       { return usuario; }

    // ─── Clase interna: datos públicos del usuario ─────────────────────────────
    /**
     * Proyección segura del usuario: excluye la contraseña.
     * Se serializa directamente en el JSON de respuesta.
     */
    public static class UsuarioInfo {
        private Long   id;
        private String nombre;
        private String correo;
        private String rol;

        public UsuarioInfo(Long id, String nombre, String correo, String rol) {
            this.id     = id;
            this.nombre = nombre;
            this.correo = correo;
            this.rol    = rol;
        }

        public Long   getId()     { return id; }
        public String getNombre() { return nombre; }
        public String getCorreo() { return correo; }
        public String getRol()    { return rol; }
    }
}
