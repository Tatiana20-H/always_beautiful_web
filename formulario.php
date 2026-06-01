<?php include("header.php"); ?>

<link rel="stylesheet" href="styles.css">

<div class="formulario-container">

    <form action="procesar_formulario.php" method="POST" class="formulario">

        <h2>Contáctanos 💌</h2>

        <input type="text" name="nombre" placeholder="Nombre completo" required>

        <input type="email" name="correo" placeholder="Correo electrónico" required>

        <input type="text" name="asunto" placeholder="Asunto" required>

        <textarea name="mensaje" placeholder="Escribe tu mensaje..." required></textarea>

        <button type="submit">Enviar mensaje</button>

    </form>

</div>