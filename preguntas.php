<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Preguntas Frecuentes - Always Beautiful</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<?php include("header.php"); ?>

<main class="legal-page legal-faq">
    <header class="legal-hero">
        <p class="legal-eyebrow">Centro de ayuda</p>
        <h1>Preguntas frecuentes</h1>
        <p>Respuestas rápidas sobre compras, pagos, envíos y tu cuenta.</p>
    </header>
    <section class="faq-lista" aria-label="Preguntas frecuentes">
        <p class="faq-categoria">Compras y pagos</p>
        <details class="faq-item"><summary>¿Cómo puedo realizar una compra?</summary><p>Selecciona los productos que deseas, agrégalos al carrito y luego dirígete a la opción de pagar.</p></details>
        <details class="faq-item"><summary>¿Qué métodos de pago aceptan?</summary><p>Actualmente manejamos pagos simulados dentro de la plataforma para fines educativos.</p></details>

        <p class="faq-categoria">Envíos y devoluciones</p>
        <details class="faq-item"><summary>¿Cuánto tarda el envío?</summary><p>Los tiempos de entrega pueden variar entre 2 a 5 días hábiles dependiendo de tu ubicación.</p></details>
        <details class="faq-item"><summary>¿Puedo devolver un producto?</summary><p>Las devoluciones están sujetas a revisión. <a href="formulario.php">Contáctanos</a> para evaluar tu caso.</p></details>

        <p class="faq-categoria">Cuenta y privacidad</p>
        <details class="faq-item"><summary>¿Mis datos están seguros?</summary><p>Sí. Consulta nuestra <a href="privacidad.php">Política de Privacidad</a> para conocer cómo cuidamos tu información.</p></details>
        <details class="faq-item"><summary>¿Necesito crear una cuenta para comprar?</summary><p>No es necesario; puedes comprar como invitado sin registrarte.</p></details>

        <p class="faq-categoria">Atención al cliente</p>
        <details class="faq-item"><summary>¿Cómo puedo contactar con soporte?</summary><p>Puedes escribirnos desde el <a href="formulario.php">formulario de contacto</a>. Si tienes cuenta, podrás consultar nuestras respuestas en tu bandeja.</p></details>
        <a href="inicio.php" class="legal-volver">Volver al inicio <span aria-hidden="true">→</span></a>
    </section>
</main>

</body>
</html>