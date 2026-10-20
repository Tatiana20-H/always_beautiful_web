<?php 
session_start();
include("header.php");
?>

<main class="articulos-container">
    <header class="articulos-encabezado">
        <p class="articulos-eyebrow">Guías y consejos</p>
        <h1>Artículos de belleza</h1>
        <p>Ideas sencillas para cuidar tu piel, maquillarte y mantener tu cabello saludable.</p>
    </header>

    <div class="articulos-grid">
        <article class="articulo-card">
            <a class="articulo-card-imagen" href="articulo1.php" aria-label="Leer Rutina básica de skincare">
                <img src="IMG/blog1.jpg" alt="Mujer aplicando productos para el cuidado facial" loading="lazy">
            </a>
            <div class="contenido">
                <p class="articulo-categoria">Cuidado de la piel</p>
                <h2>Rutina básica de skincare</h2>
                <p>Ordena los pasos esenciales de mañana y noche para cuidar tu piel con constancia.</p>
                <a class="articulo-enlace" href="articulo1.php">Leer artículo <span aria-hidden="true">→</span></a>
            </div>
        </article>

        <article class="articulo-card">
            <a class="articulo-card-imagen" href="articulo2.php" aria-label="Leer Maquillaje natural">
                <img src="IMG/blog2.jpg" alt="Maquillaje en tonos suaves para un acabado natural" loading="lazy">
            </a>
            <div class="contenido">
                <p class="articulo-categoria">Maquillaje</p>
                <h2>Maquillaje natural</h2>
                <p>Una guía práctica para preparar la piel y conseguir un acabado ligero y cómodo.</p>
                <a class="articulo-enlace" href="articulo2.php">Leer artículo <span aria-hidden="true">→</span></a>
            </div>
        </article>

        <article class="articulo-card">
            <a class="articulo-card-imagen" href="articulo3.php" aria-label="Leer Cuidado del cabello">
                <img src="IMG/blog3.jpg" alt="Consejos de cuidado y peinado para el cabello" loading="lazy">
            </a>
            <div class="contenido">
                <p class="articulo-categoria">Cuidado del cabello</p>
                <h2>Cuidado del cabello</h2>
                <p>Hábitos de lavado, acondicionamiento y protección para una rutina capilar sencilla.</p>
                <a class="articulo-enlace" href="articulo3.php">Leer artículo <span aria-hidden="true">→</span></a>
            </div>
        </article>
    </div>
</main>

</body>
</html>