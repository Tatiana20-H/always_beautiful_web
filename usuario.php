<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'usuario') {
    header("Location: login.php");
    exit();
}
?>