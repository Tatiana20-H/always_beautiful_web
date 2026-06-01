package com.beauty.alwaysbeautiful.dto;

// DTO (Data Transfer Object) para las peticiones de registro e inicio de sesión.
// Separa los datos que viajan por la red del modelo interno de la base de datos.

import jakarta.validation.constraints.Email;
import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;

/**
 * RegistroRequest: datos que el cliente envía al endpoint POST /api/auth/registro
 * Contiene las validaciones mínimas antes de procesar el registro.
 */
public class RegistroRequest {

    // Nombre completo del nuevo usuario
    @NotBlank(message = "El nombre es obligatorio")
    @Size(min = 2, max = 100, message = "El nombre debe tener entre 2 y 100 caracteres")
    private String nombre;

    // Correo único que identifica la cuenta
    @NotBlank(message = "El correo es obligatorio")
    @Email(message = "Ingrese un correo válido")
    private String correo;

    // Contraseña en texto plano (se encriptará en el servicio, nunca se guarda tal cual)
    @NotBlank(message = "La contraseña es obligatoria")
    @Size(min = 6, message = "La contraseña debe tener al menos 6 caracteres")
    private String contrasena;

    // Getters y Setters
    public String getNombre()              { return nombre; }
    public void setNombre(String nombre)   { this.nombre = nombre; }

    public String getCorreo()              { return correo; }
    public void setCorreo(String correo)   { this.correo = correo; }

    public String getContrasena()          { return contrasena; }
    public void setContrasena(String c)    { this.contrasena = c; }
}
