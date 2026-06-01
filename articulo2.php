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
    color: #b8869a;
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
    color: #b8869a;
    margin-bottom: 10px;
}
</style>

<div class="articulo-container">

    <h2>Maquillaje natural</h2>

    <img src="IMG/blog2.jpg">

    <p>
        El maquillaje natural es perfecto para el día a día, ya que resalta tu belleza sin recargar el rostro.
        La idea es lucir fresca, radiante y sencilla.
    </p>

    <p>
        Sigue estos pasos para lograr un look natural en pocos minutos:
    </p>

    <div class="pasos">

        <h3>Paso 1: Preparar la piel</h3>
        <p>Aplica una crema hidratante y protector solar para una base saludable.</p>

        <h3>Paso 2: Base ligera</h3>
        <p>Usa una base o BB cream ligera para unificar el tono de tu piel sin exagerar.</p>

        <h3>Paso 3: Rubor suave</h3>
        <p>Aplica un poco de rubor para dar un efecto fresco y natural a tus mejillas.</p>

        <h3>Paso 4: Ojos sencillos</h3>
        <p>Utiliza sombras en tonos neutros y una capa ligera de pestañina.</p>

        <h3>Paso 5: Labios naturales</h3>
        <p>Usa gloss o labial en tonos suaves para un acabado delicado.</p>

    </div>

    <p>
      Tip: Menos es más. Un maquillaje natural resalta tu belleza real sin necesidad de exagerar.
    </p>

</div>

</body>
</html>