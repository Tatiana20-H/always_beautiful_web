import java.sql.*;
import java.util.*;

import dao.ConexionBD;

public class UsuarioDAO {

    public void insertar(Usuario u) {
        String sql = "INSERT INTO usuarios(nombre, correo, rol) VALUES(?,?,?)";

        try (Connection con = ConexionBD.conectar();
             PreparedStatement ps = con.prepareStatement(sql)) {

            ps.setString(1, u.getNombre());
            ps.setString(2, u.getCorreo());
            ps.setString(3, u.getRol());
            ps.executeUpdate();

            System.out.println("Usuario registrado");

        } catch (Exception e) {
            System.out.println(e.getMessage());
        }
    }

    public List<Usuario> listar() {
        List<Usuario> lista = new ArrayList<>();
        String sql = "SELECT * FROM usuarios";

        try (Connection con = ConexionBD.conectar();
             Statement st = con.createStatement();
             ResultSet rs = st.executeQuery(sql)) {

            while (rs.next()) {
                lista.add(new Usuario(
                        rs.getInt("id"),
                        rs.getString("nombre"),
                        rs.getString("correo"),
                        rs.getString("rol")
                ));
            }

        } catch (Exception e) {
            System.out.println(e.getMessage());
        }

        return lista;
    }
}