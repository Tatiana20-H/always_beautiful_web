package com.beauty.alwaysbeautiful.service;

// Capa de servicio: contiene toda la lógica de negocio del módulo de autenticación.
// El controlador solo delega; aquí se validan las reglas y se interactúa con el repositorio.

import com.beauty.alwaysbeautiful.dto.AuthResponse;
import com.beauty.alwaysbeautiful.dto.LoginRequest;
import com.beauty.alwaysbeautiful.dto.RegistroRequest;
import com.beauty.alwaysbeautiful.model.Usuario;
import com.beauty.alwaysbeautiful.repository.UsuarioRepository;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;

import java.util.Optional;

/**
 * AuthService: lógica de negocio para registro e inicio de sesión.
 *
 * Reglas de negocio:
 *  - No se permiten dos cuentas con el mismo correo.
 *  - Las contraseñas se encriptan con BCrypt antes de persistirlas.
 *  - En el login se compara la contraseña plana contra el hash almacenado.
 *  - La respuesta nunca expone la contraseña (ni cifrada) al cliente.
 */
@Service
public class AuthService {

    // Repositorio de acceso a datos de usuarios
    private final UsuarioRepository usuarioRepo;

    // Encriptador de contraseñas (configurado como BCryptPasswordEncoder en SecurityConfig)
    private final PasswordEncoder passwordEncoder;

    // Inyección de dependencias por constructor (buena práctica en Spring)
    public AuthService(UsuarioRepository usuarioRepo, PasswordEncoder passwordEncoder) {
        this.usuarioRepo     = usuarioRepo;
        this.passwordEncoder = passwordEncoder;
    }

    // ─── REGISTRO ─────────────────────────────────────────────────────────────

    /**
     * Registra un nuevo usuario en el sistema.
     *
     * Flujo:
     *  1. Verificar que el correo no esté ya registrado.
     *  2. Encriptar la contraseña con BCrypt.
     *  3. Persistir el usuario con rol "CLIENTE" por defecto.
     *  4. Retornar respuesta de éxito o error según corresponda.
     *
     * @param request datos del nuevo usuario (nombre, correo, contraseña)
     * @return AuthResponse con el resultado de la operación
     */
    public AuthResponse registrar(RegistroRequest request) {

        // Paso 1: verificar si el correo ya existe en la base de datos
        if (usuarioRepo.existsByCorreo(request.getCorreo())) {
            // El correo ya está en uso → error de registro
            return new AuthResponse(false,
                "Error en el registro: el correo '" + request.getCorreo() + "' ya está registrado");
        }

        // Paso 2: encriptar la contraseña (NUNCA guardar texto plano)
        String contrasenaHash = passwordEncoder.encode(request.getContrasena());

        // Paso 3: crear la entidad Usuario con rol predeterminado "CLIENTE"
        Usuario nuevoUsuario = new Usuario(
                request.getNombre(),
                request.getCorreo(),
                contrasenaHash,
                "CLIENTE"   // los administradores se asignan manualmente
        );

        // Paso 4: persistir en la base de datos
        Usuario guardado = usuarioRepo.save(nuevoUsuario);

        // Paso 5: construir y retornar respuesta exitosa (sin contraseña)
        AuthResponse.UsuarioInfo info = new AuthResponse.UsuarioInfo(
                guardado.getId(),
                guardado.getNombre(),
                guardado.getCorreo(),
                guardado.getRol()
        );

        return new AuthResponse(true, "Registro exitoso. ¡Bienvenido/a a Always Beautiful!", info);
    }

    // ─── INICIO DE SESIÓN ─────────────────────────────────────────────────────

    /**
     * Valida las credenciales y realiza el inicio de sesión.
     *
     * Flujo:
     *  1. Buscar el usuario por correo.
     *  2. Si no existe → error de autenticación.
     *  3. Comparar la contraseña enviada con el hash almacenado (BCrypt).
     *  4. Si coinciden → autenticación satisfactoria.
     *  5. Si no coinciden → error en la autenticación.
     *
     * NOTA DE SEGURIDAD: el mensaje de error no especifica si el correo
     * o la contraseña son incorrectos, para evitar enumeración de usuarios.
     *
     * @param request credenciales del usuario (correo y contraseña)
     * @return AuthResponse con el resultado de la operación
     */
    public AuthResponse login(LoginRequest request) {

        // Paso 1: buscar el usuario por correo electrónico
        Optional<Usuario> optUsuario = usuarioRepo.findByCorreo(request.getCorreo());

        // Paso 2: si no existe el correo, denegar acceso
        if (optUsuario.isEmpty()) {
            // Mensaje genérico para no revelar si el correo existe
            return new AuthResponse(false, "Error en la autenticación: credenciales incorrectas");
        }

        Usuario usuario = optUsuario.get();

        // Paso 3: comparar la contraseña en texto plano con el hash BCrypt
        boolean contrasenaCorrecta = passwordEncoder.matches(
                request.getContrasena(),  // contraseña enviada por el cliente
                usuario.getContrasena()   // hash almacenado en la base de datos
        );

        // Paso 4: si la contraseña no coincide, denegar acceso
        if (!contrasenaCorrecta) {
            return new AuthResponse(false, "Error en la autenticación: credenciales incorrectas");
        }

        // Paso 5: autenticación satisfactoria → retornar datos del usuario (sin hash)
        AuthResponse.UsuarioInfo info = new AuthResponse.UsuarioInfo(
                usuario.getId(),
                usuario.getNombre(),
                usuario.getCorreo(),
                usuario.getRol()
        );

        return new AuthResponse(true, "Autenticación satisfactoria. ¡Bienvenido/a, " + usuario.getNombre() + "!", info);
    }
}
