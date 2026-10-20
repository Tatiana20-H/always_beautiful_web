<?php 
session_start();
include("header.php");
?>

<main class="articulo-container">
    <a class="articulo-volver" href="articulos.php"><span aria-hidden="true">←</span> Volver a artículos</a>
    <header class="articulo-portada">
        <p class="articulo-categoria">Cuidado de la piel</p>
        <h1>Rutina básica de skincare</h1>
        <p class="articulo-bajada">Una rutina sencilla, constante y adaptada a las necesidades de tu piel.</p>
        <img src="IMG/blog1.jpg" alt="Mujer aplicando productos para el cuidado facial">
    </header>
    <div class="articulo-cuerpo">
        <p>Cuidar la piel no requiere una colección extensa de productos. Una rutina corta, realizada en el orden correcto y de forma constante, ayuda a mantenerla limpia, cómoda e hidratada.</p>
        <p>Empieza con lo esencial y observa cómo responde tu piel. Si un producto causa irritación persistente, suspende su uso y consulta a un profesional de salud.</p>
        <section class="pasos" aria-label="Pasos de la rutina de skincare">
            <details><summary><span>01</span>Limpia con suavidad</summary><p>Usa un limpiador adecuado para tu tipo de piel, por la mañana y por la noche. Evita frotar con fuerza y enjuaga con agua tibia.</p></details>
            <details><summary><span>02</span>Aplica tratamientos</summary><p>Si usas un sérum o tratamiento, aplícalo después de limpiar y sigue las indicaciones del envase. Incorpora un producto nuevo a la vez.</p></details>
            <details><summary><span>03</span>Hidrata</summary><p>Elige una crema que se sienta cómoda en tu piel. La hidratación ayuda a mantener una sensación suave y a cuidar la barrera cutánea.</p></details>
            <details><summary><span>04</span>Protege del sol</summary><p>Por la mañana, termina con protector solar de amplio espectro. Sigue las instrucciones del producto y reaplica según sea necesario.</p></details>
        </section>
        <aside class="articulo-consejo"><strong>Para recordar</strong><p>La constancia y la protección solar diaria son más importantes que añadir muchos pasos a la rutina.</p></aside>
        <a class="articulo-volver articulo-volver-final" href="articulos.php">Explorar los otros artículos <span aria-hidden="true">→</span></a>
    </div>
</main>

</body>
</html>