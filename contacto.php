<?php 
session_start();
include("header.php");
?>

<link rel="stylesheet" href="styles.css">

<style>
.contacto-container{
    max-width: 600px;
    margin: 50px auto;
    background: #ffe6f0;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.contacto-container h2{
    text-align: center;
    color: #d63384;
    margin-bottom: 20px;
}

.contacto-container input,
.contacto-container textarea{
    width: 100%;
    padding: 12px;
    margin: 10px 0;
    border-radius: 10px;
    border: none;
}

.contacto-container button{
    width: 100%;
    padding: 12px;
    background: #d63384;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
}
</style>

<div class="contacto-container">

    <h2>Contáctanos 💌</h2>

    <!-- IMPORTANTE: action correcto -->
    <form action="formulario.php" method="POST">

        <input type="text" name="nombre" placeholder="Tu nombre" required>

        <input type="email" name="correo" placeholder="Tu correo" required>

        <textarea name="mensaje" rows="5" placeholder="Escribe tu mensaje..." required></textarea>

        <button type="submit">Enviar mensaje</button>

    </form>

</div>

</body>
</html>