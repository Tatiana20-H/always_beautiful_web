package dao;

import java.sql.*;
import java.util.*;

import conexion.ConexionBD;
import modelo.Usuario;

public class UsuarioDAO {

    public void insertar(Usuario u) {
        String sql = "INSERT INTO usuarios(nombre, correo, contrasena, rol) VALUES(?,?,?,?)";

        try (Connection con = ConexionBD.getConnection();
             PreparedStatement ps = con.prepareStatement(sql)) {

            ps.setString(1, u.getNombre());
            ps.setString(2, u.getCorreo());
            ps.setString(3, u.getContrasena());
            ps.setString(4, u.getRol());
            ps.executeUpdate();

            System.out.println("Usuario registrado");

        } catch (SQLException e) {
            System.out.println("Error al insertar: " + e.getMessage());
        }
    }

    public Usuario validar(String correo, String clave) {
        String sql = "SELECT * FROM usuarios WHERE correo = ? AND contrasena = ?";

        try (Connection con = ConexionBD.getConnection();
             PreparedStatement ps = con.prepareStatement(sql)) {

            ps.setString(1, correo);
            ps.setString(2, clave);

            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return new Usuario(
                            rs.getInt("id"),
                            rs.getString("nombre"),
                            rs.getString("correo"),
                            rs.getString("contrasena"),
                            rs.getString("rol")
                    );
                }
            }

        } catch (SQLException e) {
            System.out.println("Error en validar usuario: " + e.getMessage());
        }

        return null;
    }

    public List<Usuario> listar() {
        List<Usuario> lista = new ArrayList<>();
        String sql = "SELECT * FROM usuarios";

        try (Connection con = ConexionBD.getConnection();
             Statement st = con.createStatement();
             ResultSet rs = st.executeQuery(sql)) {

            while (rs.next()) {
                Usuario u = new Usuario(
                        rs.getInt("id"),
                        rs.getString("nombre"),
                        rs.getString("correo"),
                        rs.getString("rol")
                );
                lista.add(u);
            }

        } catch (SQLException e) {
            System.out.println("Error al listar: " + e.getMessage());
        }

        return lista;
    }
}