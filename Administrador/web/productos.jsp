<%@ page import="java.util.List" %>
<%@ page import="modelo.Producto" %>
<%@ page contentType="text/html;charset=UTF-8" %>

<!DOCTYPE html>
<html>
<head>
    <title>Productos</title>
</head>
<body>

<h2>Lista de Productos</h2>

<%
List<Producto> productos = (List<Producto>) request.getAttribute("productos");

if(productos != null){
    for(Producto p : productos){
%>

    <p>
        <strong><%= p.getNombre() %></strong> - $<%= p.getPrecio() %>
    </p>

    <form action="CarritoServlet" method="post">
        <input type="hidden" name="id" value="<%= p.getId() %>">
        <button type="submit">Agregar al carrito</button>
    </form>

    <hr>

<%
    }
} else {
%>
    <p>No hay productos disponibles.</p>
<%
}
%>

</body>
</html>