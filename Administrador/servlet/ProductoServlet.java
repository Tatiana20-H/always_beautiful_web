package Administrador.servlet;

import dao.ProductoDAO;
import modelo.Producto;

import java.io.IOException;
import java.util.List;

import javax.servlet.ServletException;
import javax.servlet.annotation.WebServlet;
import javax.servlet.http.*;

@WebServlet("/ProductoServlet")
public class ProductoServlet extends HttpServlet {

    @Override
    protected void doGet(HttpServletRequest request, HttpServletResponse response)
            throws ServletException, IOException {

        ProductoDAO dao = new ProductoDAO();
        List<Producto> lista = dao.listar();

        request.setAttribute("productos", lista);
        request.getRequestDispatcher("/productos.jsp").forward(request, response);
    }
}