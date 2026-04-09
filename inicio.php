<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Inicio - Always Beautiful</title>
<link rel="stylesheet" href="styles.css">
</head>

<body>

<?php include("header.php"); ?>

<section style="text-align:center; padding:30px;">
    <h2>Bienvenida a Always Beautiful</h2>
    <p>
        Descubre los mejores productos de belleza diseñados para resaltar tu estilo y elegancia.
        En nuestra tienda encontrarás calidad, confianza y todo lo que necesitas para sentirte única 💖
    </p>
</section>

<!-- SLIDER -->
<section class="slider">

    <button class="prev" onclick="cambiarSlide(-1)">❮</button>

    <img id="slide" src="IMG/slide1.jpg">

    <button class="next" onclick="cambiarSlide(1)">❯</button>

</section>

<script>
let imagenes = [
    "IMG/slide1.jpg",
    "IMG/slide2.jpg",
    "IMG/slide3.jpg"
];

let index = 0;

function cambiarSlide(direccion) {
    index += direccion;

    if (index < 0) index = imagenes.length - 1;
    if (index >= imagenes.length) index = 0;

    document.getElementById("slide").src = imagenes[index];
}
</script>

</body>
</html>

