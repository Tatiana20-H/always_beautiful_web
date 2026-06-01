<%@ page contentType="text/html;charset=UTF-8" %>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>

<h2>Iniciar Sesión</h2>

<% if(request.getParameter("error") != null){ %>
    <p style="color:red;">Credenciales incorrectas</p>
<% } %>

<form action="LoginServlet" method="post">
    <input type="text" name="correo" placeholder="Correo" required><br><br>
    <input type="password" name="clave" placeholder="Contraseña" required><br><br>
    <button type="submit">Ingresar</button>
</form>

</body>
</html>