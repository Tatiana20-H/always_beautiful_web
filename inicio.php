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

<!-- CATEGORÍAS -->
<section style="text-align: center; padding: 50px 20px;">
    <h2>Nuestras Categorías</h2>
    <p>Explora nuestros productos por categoría</p>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px;">
        <a href="Maquillaje.php" style="padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 10px; font-weight: 600; transition: 0.3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">💄 Maquillaje</a>
        
        <a href="Cabello.php" style="padding: 20px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; text-decoration: none; border-radius: 10px; font-weight: 600; transition: 0.3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">✨ Cabello</a>
        
        <a href="piel.php" style="padding: 20px; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; text-decoration: none; border-radius: 10px; font-weight: 600; transition: 0.3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">🌿 Piel</a>
        
        <a href="Accesorios.php" style="padding: 20px; background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; text-decoration: none; border-radius: 10px; font-weight: 600; transition: 0.3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">👑 Accesorios</a>
    </div>
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

