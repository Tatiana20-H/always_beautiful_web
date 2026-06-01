<?php
session_start();
include("conexion.php");

$correo = $_POST['correo'];
$contraseña = $_POST['contraseña'];

$sql = "SELECT * FROM usuarios WHERE correo='$correo' AND contraseña='$contraseña'";
$resultado = mysqli_query($conexion, $sql);

if ($resultado && mysqli_num_rows($resultado) > 0) {
    $datos = mysqli_fetch_assoc($resultado);

    $_SESSION['usuario'] = $datos['nombre'];
    $_SESSION['correo'] = $datos['correo'];
    $_SESSION['rol'] = $datos['rol'];

    // 🔥 AQUÍ RESPETA TUS SUBPÁGINAS
    if ($datos['rol'] == 'admin') {
        header("Location: Administrador/index.php");
    } else {
        header("Location: usuario.php");
    }
    exit();

} else {
    echo "<script>
        alert('Datos incorrectos');
        window.location='login.php';
    </script>";
}
?>