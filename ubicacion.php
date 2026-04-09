<?php 
session_start();
include("header.php");
?>

<link rel="stylesheet" href="styles.css">

<style>
.ubicacion-container{
    max-width: 900px;
    margin: 50px auto;
    background: #ffe6f0;
    padding: 40px;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.ubicacion-container h2{
    color: #d63384;
    margin-bottom: 20px;
}

.ubicacion-container p{
    color: #555;
    margin-bottom: 20px;
}

.mapa{
    width: 100%;
    height: 400px;
    border-radius: 15px;
    border: none;
}
</style>

<div class="ubicacion-container">

    <h2>Nuestra Ubicación 📍</h2>

    <p>
        Visítanos en Neiva, Huila +  
        Estamos listos para atenderte y ayudarte a encontrar lo mejor en belleza.
    </p>

    <p>
        📍 Neiva, Huila - Colombia
    </p>

    <!-- MAPA DE NEIVA -->
    <iframe 
        class="mapa"
        src="https://www.google.com/maps?q=Neiva+Huila+Colombia&output=embed"
        loading="lazy">
    </iframe>

</div>

</body>
</html>