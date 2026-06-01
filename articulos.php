<?php 
session_start();
include("header.php");
?>

<link rel="stylesheet" href="styles.css">

<style>
.articulos-container{
    max-width: 1100px;
    margin: 50px auto;
    padding: 20px;
}

.articulos-container h2{
    text-align: center;
    color: #b8869a;
    font-size: 32px;
    margin-bottom: 30px;
}

.articulos-grid{
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.articulo-card{
    background: #ffe6f0;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: 0.3s;
}

.articulo-card:hover{
    transform: scale(1.03);
}

.articulo-card img{
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.articulo-card .contenido{
    padding: 20px;
}

.articulo-card h3{
    color: #b8869a;
    margin-bottom: 10px;
}

.articulo-card p{
    color: #555;
    font-size: 15px;
}

.articulo-card a{
    display: inline-block;
    margin-top: 10px;
    background: #525252;
    color: white;
    padding: 8px 15px;
    border-radius: 10px;
    text-decoration: none;
}
</style>

<div class="articulos-container">

    <h2>Artículos de Belleza </h2>

    <div class="articulos-grid">

        <!-- ARTÍCULO 1 -->
        <div class="articulo-card">
            <img src="IMG/blog1.jpg">
            <div class="contenido">
                <h3>Rutina básica de skincare</h3>
                <p>Aprende los pasos esenciales para cuidar tu piel todos los días.</p>
                <a href="articulo1.php">Leer más</a>
            </div>
        </div>

        <!-- ARTÍCULO 2 -->
        <div class="articulo-card">
            <img src="IMG/blog2.jpg">
            <div class="contenido">
                <h3>Maquillaje natural</h3>
                <p>Descubre cómo lograr un look natural y hermoso en minutos.</p>
                <a href="articulo2.php">Leer más</a>
            </div>
        </div>

        <!-- ARTÍCULO 3 -->
        <div class="articulo-card">
            <img src="IMG/blog3.jpg">
            <div class="contenido">
                <h3>Cuidado del cabello</h3>
                <p>Consejos para mantener tu cabello sano, brillante y fuerte.</p>
                <a href="articulo3.php">Leer más</a>
            </div>
        </div>

    </div>

</div>

</body>
</html>