<?php
session_start();

// Si no hay usuario registrado, redirigir a registro
if (!isset($_SESSION['usuario_id'])) {
    header("Location: registro.php");
    exit();
}

$nombre = $_SESSION['nombre'] ?? 'Usuario';
unset($_SESSION['nombre']); // Limpiar la sesión después de mostrar
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Registro Exitoso! - Always Beautiful</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #ff6b9d;
            pointer-events: none;
            animation: fall 3s linear infinite;
        }
        
        @keyframes fall {
            to {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
        
        .confirmation-container {
            text-align: center;
            background: white;
            padding: 60px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 90%;
            animation: popIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            z-index: 10;
        }
        
        @keyframes popIn {
            0% {
                transform: scale(0.3);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .checkmark {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.6s ease-out;
        }
        
        @keyframes scaleIn {
            0% {
                transform: scale(0);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .checkmark::after {
            content: '✓';
            font-size: 50px;
            color: white;
            font-weight: bold;
        }
        
        h1 {
            font-size: 36px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .subtitle {
            font-size: 20px;
            color: #667eea;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .message {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .user-name {
            font-size: 24px;
            color: #ff6b9d;
            font-weight: 700;
            margin: 20px 0;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 35px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 15px 35px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            border: 2px solid #667eea;
        }
        
        .btn-secondary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-3px);
        }
        
        .features {
            background: #f8f9ff;
            padding: 25px;
            border-radius: 15px;
            margin-top: 35px;
            text-align: left;
        }
        
        .features h3 {
            color: #333;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin: 12px 0;
            color: #666;
        }
        
        .feature-item::before {
            content: '★';
            color: #ff6b9d;
            font-size: 20px;
            margin-right: 12px;
        }
    </style>
</head>
<body>

<div class="confirmation-container">
    <div class="checkmark"></div>
    
    <div class="subtitle">¡Bienvenido a Always Beautiful!</div>
    <h1>¡Registro Exitoso! 🎉</h1>
    
    <div class="user-name"><?php echo htmlspecialchars($nombre); ?></div>
    
    <p class="message">
        Tu cuenta ha sido creada correctamente. <br>
        Ya puedes disfrutar de todos nuestros productos y servicios.
    </p>
    
    <div class="features">
        <h3>✨ Lo que puedes hacer ahora:</h3>
        <div class="feature-item">Explorar nuestro catálogo de productos</div>
        <div class="feature-item">Agregar productos a tu carrito</div>
        <div class="feature-item">Realizar compras de forma segura</div>
        <div class="feature-item">Ver tu historial de compras</div>
        <div class="feature-item">Actualizar tu perfil</div>
    </div>
    
    <div class="button-group">
        <a href="inicio.php" class="btn btn-primary">🛍️ Ir a la Tienda</a>
        <a href="perfil.php" class="btn btn-secondary">👤 Mi Perfil</a>
    </div>
</div>

<script>
    // Crear confetti (papelitos de celebración)
    function createConfetti() {
        const colors = ['#ff6b9d', '#667eea', '#764ba2', '#ffeb3b', '#ff9800'];
        const confettiPiece = document.createElement('div');
        confettiPiece.classList.add('confetti');
        confettiPiece.style.left = Math.random() * window.innerWidth + 'px';
        confettiPiece.style.background = colors[Math.floor(Math.random() * colors.length)];
        confettiPiece.style.animationDelay = Math.random() * 0.5 + 's';
        document.body.appendChild(confettiPiece);
        
        setTimeout(() => confettiPiece.remove(), 3000);
    }
    
    // Generar confetti cada 100ms
    for (let i = 0; i < 50; i++) {
        setTimeout(createConfetti, i * 100);
    }
</script>

</body>
</html>
