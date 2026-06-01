package com.beauty.alwaysbeautiful.security;

// Configuración de seguridad de Spring Security.
// Define qué endpoints son públicos y cuáles requieren autenticación,
// y registra el encriptador de contraseñas BCrypt en el contexto de Spring.

import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.security.config.annotation.web.builders.HttpSecurity;
import org.springframework.security.config.annotation.web.configuration.EnableWebSecurity;
import org.springframework.security.config.http.SessionCreationPolicy;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.security.web.SecurityFilterChain;

/**
 * SecurityConfig: configuración central de Spring Security para la API.
 *
 * Política adoptada:
 *  - API stateless (sin sesiones HTTP): adecuada para servicios REST.
 *  - Los endpoints /api/auth/** son públicos (registro y login no requieren token).
 *  - Todos los demás endpoints quedan protegidos (extensible para futuras rutas).
 *  - BCrypt como algoritmo de hash de contraseñas (factor de costo 12 por defecto).
 */
@Configuration
@EnableWebSecurity
public class SecurityConfig {

    /**
     * Cadena de filtros de seguridad HTTP.
     * Configura CORS, CSRF, autorización de rutas y política de sesión.
     */
    @Bean
    public SecurityFilterChain filterChain(HttpSecurity http) throws Exception {

        http
            // Desactivar CSRF: no es necesario en APIs REST stateless con JSON
            .csrf(csrf -> csrf.disable())

            // Configurar autorización de rutas
            .authorizeHttpRequests(auth -> auth
                // Endpoints de autenticación: acceso público (no requieren token)
                .requestMatchers("/api/auth/**").permitAll()
                // Cualquier otra ruta requiere autenticación (para futuras expansiones)
                .anyRequest().authenticated()
            )

            // Política sin sesiones: cada petición es independiente (REST puro)
            .sessionManagement(session ->
                session.sessionCreationPolicy(SessionCreationPolicy.STATELESS)
            );

        return http.build();
    }

    /**
     * Bean del encriptador de contraseñas.
     * BCrypt es el estándar recomendado: genera un salt aleatorio y aplica
     * múltiples rondas de hash, haciendo los ataques de fuerza bruta muy lentos.
     *
     * Este bean es inyectado automáticamente en AuthService.
     *
     * @return instancia de BCryptPasswordEncoder
     */
    @Bean
    public PasswordEncoder passwordEncoder() {
        // Factor de costo 12: ~250ms por hash (buen balance seguridad/rendimiento)
        return new BCryptPasswordEncoder(12);
    }
}
