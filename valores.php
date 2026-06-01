<?php 
session_start();
include("header.php");
?>

<link rel="stylesheet" href="styles.css">

<style>
.valores-container{
    max-width: 1000px;
    margin: 50px auto;
    background: #ffe6f0;
    padding: 40px;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.valores-container h2{
    color: #b8869a;
    font-size: 32px;
    margin-bottom: 30px;
}

.valores-grid{
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.valor{
    background: white;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 5px 10px rgba(0,0,0,0.1);
}

.valor h3{
    color: #b8869a;
    margin-bottom: 10px;
}

.valor p{
    color: #555;
    font-size: 15px;
}
</style>

<div class="valores-container">

    <h2>Nuestros Valores</h2>

    <div class="valores-grid">

        <div class="valor">
            <h3>Amor propio</h3>
            <p>Promovemos la confianza y el cuidado personal en cada uno de nuestros clientes.</p>
        </div>

        <div class="valor">
            <h3>Calidad</h3>
            <p>Ofrecemos productos seleccionados con altos estándares para garantizar satisfacción.</p>
        </div>

        <div class="valor">
            <h3>Compromiso</h3>
            <p>Trabajamos cada día para brindar la mejor experiencia de compra.</p>
        </div>

        <div class="valor">
            <h3>Innovación</h3>
            <p>Nos mantenemos actualizados con las últimas tendencias en belleza.</p>
        </div>

        <div class="valor">
            <h3>Responsabilidad</h3>
            <p>Actuamos con transparencia y respeto hacia nuestros clientes.</p>
        </div>

        <div class="valor">
            <h3>Pasión</h3>
            <p>Amamos lo que hacemos y lo reflejamos en cada detalle de nuestra tienda.</p>
        </div>

    </div>

</div>

</body>
</html>