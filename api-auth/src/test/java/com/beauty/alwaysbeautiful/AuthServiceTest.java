package com.beauty.alwaysbeautiful;

// Pruebas unitarias del servicio de autenticación.
// Verifican el comportamiento de registro y login sin tocar la base de datos real.

import com.beauty.alwaysbeautiful.dto.AuthResponse;
import com.beauty.alwaysbeautiful.dto.LoginRequest;
import com.beauty.alwaysbeautiful.dto.RegistroRequest;
import com.beauty.alwaysbeautiful.model.Usuario;
import com.beauty.alwaysbeautiful.repository.UsuarioRepository;
import com.beauty.alwaysbeautiful.service.AuthService;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.DisplayName;
import org.junit.jupiter.api.Test;
import org.mockito.InjectMocks;
import org.mockito.Mock;
import org.mockito.MockitoAnnotations;
import org.springframework.security.crypto.password.PasswordEncoder;

import java.util.Optional;

import static org.junit.jupiter.api.Assertions.*;
import static org.mockito.ArgumentMatchers.any;
import static org.mockito.ArgumentMatchers.anyString;
import static org.mockito.Mockito.*;

/**
 * AuthServiceTest: pruebas unitarias del servicio de autenticación.
 * Se usa Mockito para simular el repositorio y el encoder sin BD real.
 */
class AuthServiceTest {

    // Simulaciones (mocks) de dependencias externas
    @Mock
    private UsuarioRepository usuarioRepo;

    @Mock
    private PasswordEncoder passwordEncoder;

    // Instancia del servicio a probar, con los mocks inyectados
    @InjectMocks
    private AuthService authService;

    @BeforeEach
    void setUp() {
        // Inicializar los mocks antes de cada prueba
        MockitoAnnotations.openMocks(this);
    }

    // ─── Pruebas de REGISTRO ───────────────────────────────────────────────────

    @Test
    @DisplayName("Registro exitoso con datos válidos")
    void testRegistroExitoso() {
        // ARRANGE: preparar datos de entrada y comportamiento simulado
        RegistroRequest req = new RegistroRequest();
        req.setNombre("Ana García");
        req.setCorreo("ana@mail.com");
        req.setContrasena("clave123");

        // Simular que el correo no existe aún
        when(usuarioRepo.existsByCorreo("ana@mail.com")).thenReturn(false);
        // Simular encriptación de contraseña
        when(passwordEncoder.encode("clave123")).thenReturn("$2a$hash...");
        // Simular guardado del usuario
        Usuario guardado = new Usuario("Ana García", "ana@mail.com", "$2a$hash...", "CLIENTE");
        when(usuarioRepo.save(any(Usuario.class))).thenReturn(guardado);

        // ACT: ejecutar el método a probar
        AuthResponse respuesta = authService.registrar(req);

        // ASSERT: verificar el resultado esperado
        assertTrue(respuesta.isExito(), "El registro debe ser exitoso");
        assertNotNull(respuesta.getUsuario(), "Debe retornar los datos del usuario");
        assertEquals("CLIENTE", respuesta.getUsuario().getRol());
    }

    @Test
    @DisplayName("Registro falla si el correo ya existe")
    void testRegistroCorreoDuplicado() {
        // ARRANGE: simular correo ya registrado
        RegistroRequest req = new RegistroRequest();
        req.setNombre("Juan");
        req.setCorreo("existente@mail.com");
        req.setContrasena("clave123");

        when(usuarioRepo.existsByCorreo("existente@mail.com")).thenReturn(true);

        // ACT
        AuthResponse respuesta = authService.registrar(req);

        // ASSERT
        assertFalse(respuesta.isExito(), "El registro debe fallar con correo duplicado");
        assertTrue(respuesta.getMensaje().contains("Error en el registro"));
        // Verificar que NO se intentó guardar en BD
        verify(usuarioRepo, never()).save(any());
    }

    // ─── Pruebas de LOGIN ──────────────────────────────────────────────────────

    @Test
    @DisplayName("Login exitoso con credenciales correctas → Autenticación satisfactoria")
    void testLoginExitoso() {
        // ARRANGE
        LoginRequest req = new LoginRequest();
        req.setCorreo("ana@mail.com");
        req.setContrasena("clave123");

        Usuario usuario = new Usuario("Ana García", "ana@mail.com", "$2a$hash...", "CLIENTE");
        when(usuarioRepo.findByCorreo("ana@mail.com")).thenReturn(Optional.of(usuario));
        when(passwordEncoder.matches("clave123", "$2a$hash...")).thenReturn(true);

        // ACT
        AuthResponse respuesta = authService.login(req);

        // ASSERT
        assertTrue(respuesta.isExito());
        assertTrue(respuesta.getMensaje().contains("Autenticación satisfactoria"));
        assertNotNull(respuesta.getUsuario());
    }

    @Test
    @DisplayName("Login falla si el correo no existe → Error en la autenticación")
    void testLoginCorreoInexistente() {
        // ARRANGE: simular que el correo no está en BD
        LoginRequest req = new LoginRequest();
        req.setCorreo("noexiste@mail.com");
        req.setContrasena("clave123");

        when(usuarioRepo.findByCorreo(anyString())).thenReturn(Optional.empty());

        // ACT
        AuthResponse respuesta = authService.login(req);

        // ASSERT
        assertFalse(respuesta.isExito());
        assertTrue(respuesta.getMensaje().contains("Error en la autenticación"));
    }

    @Test
    @DisplayName("Login falla con contraseña incorrecta → Error en la autenticación")
    void testLoginContrasenaIncorrecta() {
        // ARRANGE
        LoginRequest req = new LoginRequest();
        req.setCorreo("ana@mail.com");
        req.setContrasena("claveMAL");

        Usuario usuario = new Usuario("Ana García", "ana@mail.com", "$2a$hash...", "CLIENTE");
        when(usuarioRepo.findByCorreo("ana@mail.com")).thenReturn(Optional.of(usuario));
        // La contraseña NO coincide
        when(passwordEncoder.matches("claveMAL", "$2a$hash...")).thenReturn(false);

        // ACT
        AuthResponse respuesta = authService.login(req);

        // ASSERT
        assertFalse(respuesta.isExito());
        assertTrue(respuesta.getMensaje().contains("Error en la autenticación"));
    }
}
