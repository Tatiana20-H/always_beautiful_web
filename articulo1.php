<?php 
session_start();
include("header.php");
?>

<link rel="stylesheet" href="styles.css">

<style>
.articulo-container{
    max-width: 800px;
    margin: 50px auto;
    background: #ffe6f0;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.articulo-container h2{
    color: #d63384;
    margin-bottom: 20px;
}

.articulo-container img{
    width: 100%;
    border-radius: 15px;
    margin-bottom: 20px;
}

.articulo-container p{
    color: #555;
    line-height: 1.8;
    margin-bottom: 15px;
}

.pasos{
    background: white;
    padding: 20px;
    border-radius: 15px;
    margin-top: 20px;
}

.pasos h3{
    color: #d63384;
    margin-bottom: 10px;
}
</style>

<div class="articulo-container">

    <h2>Rutina básica de skincare</h2>

    <img src="IMG/blog1.jpg">

    <p>
        Tener una rutina de cuidado de la piel es fundamental para mantenerla sana, hidratada y radiante.
        No necesitas muchos productos, solo los correctos y constancia.
    </p>

    <p>
        Aquí te mostramos los pasos básicos que debes seguir todos los días para cuidar tu piel de forma adecuada:
    </p>

    <div class="pasos">

        <h3>Paso 1: Limpieza</h3>
        <p>Lava tu rostro con un limpiador suave para eliminar impurezas, grasa y maquillaje.</p>

        <h3>Paso 2: Tónico</h3>
        <p>Ayuda a equilibrar el pH de la piel y prepararla para los siguientes productos.</p>

        <h3>Paso 3: Hidratación</h3>
        <p>Aplica una crema hidratante adecuada a tu tipo de piel para mantenerla suave.</p>

        <h3>Paso 4: Protector solar</h3>
        <p>Es el paso más importante. Protege tu piel del sol y previene el envejecimiento.</p>

    </div>

    <p>
        Recuerda: La clave está en la constancia. Si sigues estos pasos todos los días,
        notarás cambios positivos en tu piel muy pronto.
    </p>

</div>

</body>
</html>