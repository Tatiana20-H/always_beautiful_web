<?php
if($_SERVER["REQUEST_METHOD"] == "POST"){

    $nombre = $_POST["nombre"];
    $correo = $_POST["correo"];
    $asunto = $_POST["asunto"];
    $mensaje = $_POST["mensaje"];

    // Guardar en sesión (opcional)
    session_start();
    $_SESSION["mensaje"] = "Mensaje enviado correctamente 💖";

    header("Location: mensaje_enviado.php");
    exit();
} else {
    echo "<h2 style='text-align:center;'>⚠️ Acceso no válido</h2>";
}
?>