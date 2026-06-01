package Administrador.servlet;

import dao.UsuarioDAO;
import modelo.Usuario;

import java.io.IOException;
import javax.servlet.ServletException;
import javax.servlet.annotation.WebServlet;
import javax.servlet.http.*;

@WebServlet("/LoginServlet")
public class LoginServlet extends HttpServlet {

    @Override
    protected void doPost(HttpServletRequest request, HttpServletResponse response)
            throws ServletException, IOException {

        String correo = request.getParameter("correo");
        String clave = request.getParameter("clave");

        if (correo == null || correo.trim().isEmpty() || clave == null || clave.trim().isEmpty()) {
            response.sendRedirect(request.getContextPath() + "/login.jsp?error=true");
            return;
        }

        UsuarioDAO dao = new UsuarioDAO();
        Usuario user = dao.validar(correo, clave);

        if (user != null) {
            HttpSession session = request.getSession();
            session.setAttribute("usuario", user);
            response.sendRedirect(request.getContextPath() + "/productos.jsp");
        } else {
            response.sendRedirect(request.getContextPath() + "/login.jsp?error=true");
        }
    }
}