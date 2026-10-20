<?php 
session_start();
include("header.php");
?>

<main class="articulo-container">
    <a class="articulo-volver" href="articulos.php"><span aria-hidden="true">←</span> Volver a artículos</a>
    <header class="articulo-portada">
        <p class="articulo-categoria">Maquillaje</p>
        <h1>Maquillaje natural</h1>
        <p class="articulo-bajada">Una guía ligera para unificar el tono y resaltar tus rasgos sin complicar la rutina.</p>
        <img src="IMG/blog2.jpg" alt="Maquillaje en tonos suaves para un acabado natural">
    </header>
    <div class="articulo-cuerpo">
        <p>Un maquillaje natural prioriza la comodidad y deja que la piel se vea como piel. Puedes adaptar cada paso a lo que te gusta: no es necesario usar todos los productos ni seguir un orden rígido.</p>
        <p>Trabaja con capas finas y difumina con calma. Así puedes añadir cobertura o color poco a poco y detenerte cuando el resultado se sienta bien.</p>
        <section class="pasos" aria-label="Pasos para un maquillaje natural">
            <details><summary><span>01</span>Prepara la piel</summary><p>Aplica hidratante y deja que se absorba. Durante el día, usa protector solar como parte de tu rutina habitual.</p></details>
            <details><summary><span>02</span>Unifica sólo donde quieras</summary><p>Elige una base ligera o corrector y difumina pequeñas cantidades. Mantén la cobertura donde la necesitas y deja libres las zonas que no requieren producto.</p></details>
            <details><summary><span>03</span>Aporta un toque de color</summary><p>Aplica rubor en poca cantidad y difumínalo hacia las sienes. Si prefieres, puedes usar el mismo tono en labios y mejillas cuando el producto sea apto para ambas zonas.</p></details>
            <details><summary><span>04</span>Define la mirada</summary><p>Prueba sombras neutras y una capa de máscara de pestañas. Peina las cejas y rellena sólo los espacios que quieras definir.</p></details>
            <details><summary><span>05</span>Termina con los labios</summary><p>Un bálsamo con color o un labial suave completa el look. Escoge una textura cómoda para llevar durante el día.</p></details>
        </section>
        <aside class="articulo-consejo"><strong>Un buen punto de partida</strong><p>Usa menos producto al principio: siempre puedes añadir otra capa si quieres más intensidad.</p></aside>
        <a class="articulo-volver articulo-volver-final" href="articulos.php">Explorar los otros artículos <span aria-hidden="true">→</span></a>
    </div>
</main>

</body>
</html>