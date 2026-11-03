<?php
if (isset($_POST['contraseña']) && !isset($_POST['password'])) {
    $_POST['password'] = $_POST['contraseña'];
}

require __DIR__ . '/login.php';
?>