package com.beauty.alwaysbeautiful.repository;

// Repositorio JPA para la entidad Usuario.
// Spring Data genera automáticamente la implementación SQL en tiempo de ejecución.

import com.beauty.alwaysbeautiful.model.Usuario;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.Optional;

/**
 * UsuarioRepository: capa de acceso a datos para la tabla "usuarios".
 *
 * Al extender JpaRepository obtenemos gratis:
 *  - save()       → INSERT o UPDATE
 *  - findById()   → SELECT por PK
 *  - findAll()    → SELECT *
 *  - delete()     → DELETE
 *
 * Los métodos adicionales se declaran aquí y Spring genera el SQL derivando del nombre.
 */
@Repository
public interface UsuarioRepository extends JpaRepository<Usuario, Long> {

    /**
     * Busca un usuario por su correo electrónico.
     * SQL generado: SELECT * FROM usuarios WHERE correo = ?
     *
     * @param correo dirección de email del usuario
     * @return Optional vacío si no existe, o con el Usuario si existe
     */
    Optional<Usuario> findByCorreo(String correo);

    /**
     * Comprueba si ya existe una cuenta con ese correo.
     * SQL generado: SELECT COUNT(*) > 0 FROM usuarios WHERE correo = ?
     *
     * @param correo dirección de email a verificar
     * @return true si el correo ya está registrado
     */
    boolean existsByCorreo(String correo);
}
