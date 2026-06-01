package com.beauty.alwaysbeautiful.model;

// Entidad JPA que representa la tabla "usuarios" en la base de datos
// Mapea cada columna de la tabla a un atributo Java

import jakarta.persistence.*;
import jakarta.validation.constraints.Email;
import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;

/**
 * Modelo de dominio: Usuario
 * Representa a un usuario registrado en el sistema Always Beautiful.
 * Los campos requeridos están anotados con validaciones de Bean Validation.
 */
@Entity
@Table(name = "usuarios",
       uniqueConstraints = @UniqueConstraint(columnNames = "correo"))
public class Usuario {

    // Identificador único autogenerado por la base de datos
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    // Nombre completo del usuario – obligatorio, máx. 100 caracteres
    @NotBlank(message = "El nombre es obligatorio")
    @Size(max = 100, message = "El nombre no puede superar 100 caracteres")
    @Column(nullable = false, length = 100)
    private String nombre;

    // Correo electrónico único que también sirve como nombre de usuario
    @NotBlank(message = "El correo es obligatorio")
    @Email(message = "Formato de correo no válido")
    @Column(nullable = false, unique = true, length = 150)
    private String correo;

    // Contraseña almacenada con hash BCrypt (nunca en texto plano)
    @NotBlank(message = "La contraseña es obligatoria")
    @Column(nullable = false)
    private String contrasena;

    // Rol del usuario: "CLIENTE" o "ADMIN"
    @Column(nullable = false, length = 20)
    private String rol;

    // ─── Constructores ─────────────────────────────────────────────────────────

    /** Constructor vacío requerido por JPA */
    public Usuario() {}

    /** Constructor completo para crear un usuario nuevo */
    public Usuario(String nombre, String correo, String contrasena, String rol) {
        this.nombre    = nombre;
        this.correo    = correo;
        this.contrasena = contrasena;
        this.rol       = rol;
    }

    // ─── Getters y Setters ─────────────────────────────────────────────────────

    public Long getId()                    { return id; }
    public void setId(Long id)             { this.id = id; }

    public String getNombre()              { return nombre; }
    public void setNombre(String nombre)   { this.nombre = nombre; }

    public String getCorreo()              { return correo; }
    public void setCorreo(String correo)   { this.correo = correo; }

    public String getContrasena()          { return contrasena; }
    public void setContrasena(String c)    { this.contrasena = c; }

    public String getRol()                 { return rol; }
    public void setRol(String rol)         { this.rol = rol; }
}
