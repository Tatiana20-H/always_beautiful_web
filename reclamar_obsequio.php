<?php
session_start();
require_once __DIR__ . '/conexion.php';

if (empty($_SESSION['usuario_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$tokenSesion = $_SESSION['csrf_obsequio'] ?? '';
$tokenRecibido = $_POST['csrf'] ?? '';
if ($tokenSesion === '' || !hash_equals($tokenSesion, $tokenRecibido)) {
    http_response_code(403);
    exit('Solicitud no válida. Recarga la página e inténtalo de nuevo.');
}

$usuarioId = (int) $_SESSION['usuario_id'];
$productoId = filter_var($_POST['producto_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($productoId === false) {
    $_SESSION['notificacion_obsequio'] = 'Selecciona un producto disponible.';
    header('Location: ver_carrito.php');
    exit();
}

$conexion->begin_transaction();
try {
    $stmt = $conexion->prepare('SELECT puntos FROM usuarios WHERE id = ? FOR UPDATE');
    $stmt->bind_param('i', $usuarioId);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conexion->prepare('SELECT nombre FROM productos WHERE id = ? AND activo = 1 AND stock > 0 FOR UPDATE');
    $stmt->bind_param('i', $productoId);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$usuario || (int) $usuario['puntos'] < 1 || !$producto) {
        $conexion->rollback();
        $_SESSION['notificacion_obsequio'] = !$producto
            ? 'El producto elegido ya no está disponible.'
            : 'Necesitas al menos 1 punto para reclamar un obsequio.';
        header('Location: ver_carrito.php');
        exit();
    }

    $stmt = $conexion->prepare('UPDATE usuarios SET puntos = puntos - 1 WHERE id = ? AND puntos >= 1');
    $stmt->bind_param('i', $usuarioId);
    $stmt->execute();
    $puntoDescontado = $stmt->affected_rows === 1;
    $stmt->close();

    $stmt = $conexion->prepare('UPDATE productos SET stock = stock - 1 WHERE id = ? AND activo = 1 AND stock > 0');
    $stmt->bind_param('i', $productoId);
    $stmt->execute();
    $stockDescontado = $stmt->affected_rows === 1;
    $stmt->close();

    if (!$puntoDescontado || !$stockDescontado) {
        throw new RuntimeException('El producto o los puntos cambiaron durante el canje.');
    }

    $stmt = $conexion->prepare("INSERT INTO canjes_puntos (usuario_id, producto_id, puntos_usados, estado) VALUES (?, ?, 1, 'solicitado')");
    $stmt->bind_param('ii', $usuarioId, $productoId);
    $stmt->execute();
    $stmt->close();
    $conexion->commit();
    $_SESSION['notificacion_obsequio'] = 'Canje solicitado: ' . $producto['nombre'] . '. El equipo confirmará la entrega.';
} catch (Throwable $error) {
    $conexion->rollback();
    error_log('No se pudo procesar el canje de puntos: ' . $error->getMessage());
    $_SESSION['notificacion_obsequio'] = 'No se pudo procesar el canje. Inténtalo nuevamente.';
}

header('Location: ver_carrito.php');
exit();
