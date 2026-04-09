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

    <h2>Cuidado del cabello </h2>

    <img src="IMG/blog3.jpg">

    <p>
        Mantener tu cabello sano y brillante es clave para lucir una apariencia cuidada y hermosa.
        Con algunos hábitos sencillos puedes mejorar su salud y aspecto.
    </p>

    <p>
        Sigue estos consejos básicos para el cuidado diario de tu cabello:
    </p>

    <div class="pasos">

        <h3> Paso 1: Lavado adecuado</h3>
        <p>Utiliza un shampoo y acondicionador acorde a tu tipo de cabello y evita lavarlo en exceso.</p>

        <h3>Paso 2: Hidratación</h3>
        <p>Aplica mascarillas o tratamientos hidratantes al menos una vez por semana.</p>

        <h3> Paso 3: Protección térmica</h3>
        <p>Usa protector térmico antes de usar planchas o secadores para evitar daños.</p>

        <h3> Paso 4: Corte regular</h3>
        <p>Corta las puntas cada cierto tiempo para evitar el cabello maltratado.</p>

        <h3> Paso 5: Aceites naturales</h3>
        <p>Aplica aceites como coco o argán para dar brillo y nutrición.</p>

    </div>

    <p>
      Tip: Evita el uso excesivo de calor y productos químicos para mantener tu cabello fuerte y saludable.
    </p>

</div>

</body>
</html>