<?php
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // (Opcional: puedes guardar datos aquí si quieres)

    echo "
    <html>
    <head>
        <meta http-equiv='refresh' content='2;url=inicio.php'>
        <style>
            body{
                font-family: Arial;
                background:#fff0f5;
                display:flex;
                justify-content:center;
                align-items:center;
                height:100vh;
                margin:0;
            }
            .mensaje{
                background:#f3c6d3;
                padding:30px;
                border-radius:20px;
                text-align:center;
                box-shadow:0 5px 15px rgba(0,0,0,0.1);
            }
        </style>
    </head>

    <body>

        <div class='mensaje'>
            <h2>💖 Mensaje enviado</h2>
            <p>Redirigiendo al inicio...</p>
        </div>

    </body>
    </html>
    ";

    exit();

} else {
    echo "<h2 style='text-align:center;'>⚠️ Acceso no válido</h2>";
}
?>