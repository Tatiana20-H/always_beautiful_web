<?php
session_start();

// Si no hay usuario registrado, redirigir
if (!isset($_SESSION['usuario_id'])) {
    header("Location: registro.php");
    exit();
}

// Obtener el nombre desde la sesión
$nombre = $_SESSION['nombre'] ?? 'Usuario';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registro Exitoso</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Poppins', Arial, sans-serif;
    background: linear-gradient(135deg, #fff0f5, #f3d7e3);
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
}

.container{
    background:#fff;
    padding:50px 35px;
    border-radius:20px;
    text-align:center;
    box-shadow:0 10px 30px rgba(236,129,183,0.2);
    width:350px;
}

.check{
    width:90px;
    height:90px;
    background: linear-gradient(135deg, #ec81b7, #ff66a3);
    border-radius:50%;
    display:flex;
    justify-content:center;
    align-items:center;
    margin:0 auto 20px;
    color:white;
    font-size:45px;
}

h1{
    color:#333;
    margin-bottom:10px;
}

p{
    color:#777;
    font-size:14px;
}

.nombre{
    color:#ec81b7;
    font-weight:bold;
    margin:15px 0;
    font-size:18px;
}

.btn{
    display:inline-block;
    margin-top:20px;
    padding:12px 30px;
    background:#ec81b7;
    color:#fff;
    text-decoration:none;
    border-radius:10px;
    transition:0.3s;
}

.btn:hover{
    background:#d96aa0;
}
</style>

</head>

<body>

<div class="container">
    <div class="check">✓</div>

    <h1>¡Registro Exitoso!</h1>
    <p>Tu cuenta ha sido creada correctamente.</p>

    <div class="nombre">
        ¡Hola, <?php echo htmlspecialchars($nombre); ?>!
    </div>

    <p>Ya puedes iniciar sesión con tus credenciales.</p>

    <a href="index.php" class="btn">Iniciar Sesión</a>
</div>

</body>
</html>