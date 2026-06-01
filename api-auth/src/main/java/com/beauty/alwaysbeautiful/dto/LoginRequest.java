package com.beauty.alwaysbeautiful.dto;

// DTO para la petición de inicio de sesión
// Solo requiere correo y contraseña

import jakarta.validation.constraints.Email;
import jakarta.validation.constraints.NotBlank;

/**
 * LoginRequest: datos que el cliente envía al endpoint POST /api/auth/login
 */
public class LoginRequest {

    // Correo que identifica la cuenta del usuario
    @NotBlank(message = "El correo es obligatorio")
    @Email(message = "Ingrese un correo válido")
    private String correo;

    // Contraseña en texto plano para comparar contra el hash almacenado
    @NotBlank(message = "La contraseña es obligatoria")
    private String contrasena;

    // Getters y Setters
    public String getCorreo()              { return correo; }
    public void setCorreo(String correo)   { this.correo = correo; }

    public String getContrasena()          { return contrasena; }
    public void setContrasena(String c)    { this.contrasena = c; }
}
