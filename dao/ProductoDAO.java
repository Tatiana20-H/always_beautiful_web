package dao;

import modelo.Producto;
import java.sql.*;
import java.util.*;

public class ProductoDAO {

    public void insertar(Producto p) {
        String sql = "INSERT INTO productos(nombre, precio, stock) VALUES(?,?,?)";

        try (Connection con = ConexionBD.conectar();
             PreparedStatement ps = con.prepareStatement(sql)) {

            ps.setString(1, p.getNombre());
            ps.setInt(2, p.getPrecio());
            ps.setInt(3, p.getStock());
            ps.executeUpdate();

            System.out.println("Producto agregado");

        } catch (Exception e) {
            System.out.println(e.getMessage());
        }
    }

    public List<Producto> listar() {
        List<Producto> lista = new ArrayList<>();
        String sql = "SELECT * FROM productos";

        try (Connection con = ConexionBD.conectar();
             Statement st = con.createStatement();
             ResultSet rs = st.executeQuery(sql)) {

            while (rs.next()) {
                lista.add(new Producto(
                        rs.getInt("id"),
                        rs.getString("nombre"),
                        rs.getInt("precio"),
                        rs.getInt("stock")
                ));
            }

        } catch (Exception e) {
            System.out.println(e.getMessage());
        }

        return lista;
    }

    public void actualizar(Producto p) {
        String sql = "UPDATE productos SET nombre=?, precio=?, stock=? WHERE id=?";

        try (Connection con = ConexionBD.conectar();
             PreparedStatement ps = con.prepareStatement(sql)) {

            ps.setString(1, p.getNombre());
            ps.setInt(2, p.getPrecio());
            ps.setInt(3, p.getStock());
            ps.setInt(4, p.getId());
            ps.executeUpdate();

            System.out.println("Producto actualizado");

        } catch (Exception e) {
            System.out.println(e.getMessage());
        }
    }

    public void eliminar(int id) {
        String sql = "DELETE FROM productos WHERE id=?";

        try (Connection con = ConexionBD.conectar();
             PreparedStatement ps = con.prepareStatement(sql)) {

            ps.setInt(1, id);
            ps.executeUpdate();

            System.out.println("Producto eliminado");

        } catch (Exception e) {
            System.out.println(e.getMessage());
        }
    }
}