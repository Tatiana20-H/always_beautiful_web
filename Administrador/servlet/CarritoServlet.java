package Administrador.servlet;

import java.io.IOException;
import java.util.ArrayList;
import java.util.List;

import javax.servlet.ServletException;
import javax.servlet.annotation.WebServlet;
import javax.servlet.http.*;

@WebServlet("/CarritoServlet")
public class CarritoServlet extends HttpServlet {

    @Override
    protected void doPost(HttpServletRequest request, HttpServletResponse response)
            throws ServletException, IOException {

        String idParam = request.getParameter("id");
        if (idParam == null || idParam.trim().isEmpty()) {
            response.sendRedirect(request.getContextPath() + "/productos.jsp?error=missingId");
            return;
        }

        int idProducto;
        try {
            idProducto = Integer.parseInt(idParam);
        } catch (NumberFormatException e) {
            response.sendRedirect(request.getContextPath() + "/productos.jsp?error=invalidId");
            return;
        }

        HttpSession session = request.getSession();

        @SuppressWarnings("unchecked")
        List<Integer> carrito = (List<Integer>) session.getAttribute("carrito");

        if (carrito == null) {
            carrito = new ArrayList<>();
        }

        carrito.add(idProducto);
        session.setAttribute("carrito", carrito);

        response.sendRedirect(request.getContextPath() + "/carrito.jsp");
    }
}