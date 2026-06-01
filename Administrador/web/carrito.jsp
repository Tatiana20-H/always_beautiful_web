<%@ page import="java.util.List" %>
<%@ page contentType="text/html;charset=UTF-8" %>

<!DOCTYPE html>
<html>
<head>
    <title>Carrito</title>
</head>
<body>

<h2>Carrito de Compras</h2>

<%
List<Integer> carrito = (List<Integer>) session.getAttribute("carrito");

if(carrito != null && !carrito.isEmpty()){
%>

    <p>Productos agregados: <%= carrito.size() %></p>

<%
} else {
%>

    <p>El carrito está vacío</p>

<%
}
%>

</body>
</html>