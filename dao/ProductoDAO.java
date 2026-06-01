package dao;

import modelo.Producto;
import conexion.ConexionBD;

import java.sql.*;
import java.util.*;

public class ProductoDAO {

    // INSERTAR
    public void insertar(Producto p) {
        String sql = "INSERT INTO productos(nombre, precio, stock) VALUES(?,?,?)";

        try (Connection con = ConexionBD.getConnection();
             PreparedStatement ps = con.prepareStatement(sql)) {

            ps.setString(1, p.getNombre());
            ps.setDouble(2, p.getPrecio());
            ps.setInt(3, p.getStock());

            int filas = ps.executeUpdate();

            if (filas > 0) {
                System.out.println("Producto agregado: " + p.getNombre());
            }

        } catch (SQLException e) {
            System.out.println("Error al insertar: " + e.getMessage());
        }
    }

    // LISTAR
    public List<Producto> listar() {
        List<Producto> lista = new ArrayList<>();
        String sql = "SELECT * FROM productos";

        try (Connection con = ConexionBD.getConnection();
             Statement st = con.createStatement();
             ResultSet rs = st.executeQuery(sql)) {

            while (rs.next()) {
                Producto p = new Producto(
                        rs.getInt("id"),
                        rs.getString("nombre"),
                        rs.getDouble("precio"),
                        rs.getInt("stock")
                );
                lista.add(p);
            }

        } catch (SQLException e) {
            System.out.println("Error al listar: " + e.getMessage());
        }

        return lista;
    }
}