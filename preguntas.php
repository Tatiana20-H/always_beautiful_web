<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Preguntas Frecuentes - Always Beautiful</title>
<link rel="stylesheet" href="styles.css">

<style>
.container{
    width: 80%;
    margin: 40px auto;
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

h2{
    text-align: center;
    color: #d63384;
}

.pregunta{
    margin-top: 20px;
    font-weight: bold;
    color: #d63384;
}

.respuesta{
    margin: 10px 0;
    padding-left: 10px;
}

.btn{
    display: block;
    margin: 30px auto;
    background: #d63384;
    color: #fff;
    padding: 10px 20px;
    border-radius: 5px;
    text-align: center;
    text-decoration: none;
}
</style>

</head>
<body>

<?php include("header.php"); ?>

<div class="container">

<h2>Preguntas Frecuentes</h2>

<div class="pregunta">¿Cómo puedo realizar una compra?</div>
<div class="respuesta">Selecciona los productos que deseas, agrégalos al carrito y luego dirígete a la opción de pagar.</div>

<div class="pregunta">¿Qué métodos de pago aceptan?</div>
<div class="respuesta">Actualmente manejamos pagos simulados dentro de la plataforma para fines educativos.</div>

<div class="pregunta">¿Cuánto tarda el envío?</div>
<div class="respuesta">Los tiempos de entrega pueden variar entre 2 a 5 días hábiles dependiendo de tu ubicación.</div>

<div class="pregunta">¿Puedo devolver un producto?</div>
<div class="respuesta">Las devoluciones están sujetas a revisión. Puedes contactarnos para evaluar tu caso.</div>

<div class="pregunta">¿Mis datos están seguros?</div>
<div class="respuesta">Sí, protegemos tu información personal y no la compartimos con terceros sin autorización.</div>

<div class="pregunta">¿Necesito crear una cuenta para comprar?</div>
<div class="respuesta">No es necesario, puedes comprar como invitado sin registrarte.</div>

<div class="pregunta">¿Cómo puedo contactar con soporte?</div>
<div class="respuesta">Puedes dirigirte a la sección de contacto y enviarnos un mensaje.</div>

<a href="inicio.php" class="btn">Volver al inicio</a>

</div>

</body>
</html>