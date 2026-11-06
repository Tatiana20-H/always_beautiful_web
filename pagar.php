<?php
session_start();
if (strtolower((string) ($_SESSION['rol'] ?? '')) === 'admin') {
    header('Location: Administrador/index.php');
    exit();
}
include('conexion.php');
$conexion = $GLOBALS['conexion'];
require_once __DIR__ . '/precios_carrito.php';
require_once __DIR__ . '/stripe_config.php';
date_default_timezone_set('America/Bogota');

const PEDIDO_MAXIMO_COP = 4294967295;

function responderEtapaPedido($mensaje, $codigo = 400, $pedidoId = null, $referencia = '', $total = null, $puedePagar = false, $descuento = 0, $puntosUsados = 0) {
    http_response_code($codigo);
    $mensajeSeguro = htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Pedido</title><link rel="stylesheet" href="styles.css"></head><body>';
    echo '<main class="resultado-pago' . ($pedidoId ? ' pedido-pendiente' : '') . '">';
    if ($pedidoId) {
        echo '<p class="pedido-etiqueta">RESUMEN DE COMPRA</p><h1>Tu pedido</h1>';
    } else {
        echo '<h1>No se pudo crear el pedido</h1>';
    }
    echo '<p>' . $mensajeSeguro . '</p>';
    if ($pedidoId) {
        $carritoSeleccionado = $_SESSION['carrito'] ?? [];
        $unidadesSeleccionadas = 0;
        echo '<section class="pedido-productos" aria-label="Productos seleccionados">';
        foreach (is_array($carritoSeleccionado) ? $carritoSeleccionado : [] as $nombreProducto => $lineaProducto) {
            if (!is_array($lineaProducto)) {
                continue;
            }
            $cantidadProducto = normalizarCantidadCarrito($lineaProducto['cantidad'] ?? null);
            if ($cantidadProducto === null) {
                continue;
            }
            $unidadesSeleccionadas += $cantidadProducto;
            echo '<div class="pedido-producto"><span>' . htmlspecialchars((string) $nombreProducto, ENT_QUOTES, 'UTF-8') . '</span><strong>× ' . $cantidadProducto . '</strong></div>';
        }
        echo '</section>';
        echo '<div class="pedido-resumen-total"><span>' . $unidadesSeleccionadas . ($unidadesSeleccionadas === 1 ? ' unidad seleccionada' : ' unidades seleccionadas') . '</span><strong>$' . number_format((int) $total, 0, ',', '.') . ' <small>COP</small></strong></div>';
            if ($descuento > 0) {
                echo '<p class="pedido-descuento">Descuento por puntos (' . (int) $puntosUsados . ' puntos): −$' . number_format((int) $descuento, 0, ',', '.') . ' COP</p>';
            }
        if ($puedePagar) {
            try {
                $_SESSION['csrf_pago'] = bin2hex(random_bytes(32));
                $nombreCuenta = htmlspecialchars((string) ($_SESSION['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
                $correoCuenta = htmlspecialchars((string) ($_SESSION['correo'] ?? ''), ENT_QUOTES, 'UTF-8');
                $csrfPago = htmlspecialchars($_SESSION['csrf_pago'], ENT_QUOTES, 'UTF-8');
                echo '<div class="pedido-acciones"><button type="button" class="btn-pagar" id="abrir-modal-entrega">Realizar pago</button><a href="ver_carrito.php" class="pedido-volver">Volver al carrito</a></div>';
                echo '<dialog class="modal-entrega" id="modal-entrega" aria-labelledby="titulo-entrega">';
                echo '<div class="modal-entrega-encabezado"><div><p>DATOS DEL PEDIDO</p><h2 id="titulo-entrega">¿Dónde lo entregamos?</h2></div><button type="button" class="modal-entrega-cerrar" aria-label="Cerrar" id="cerrar-modal-entrega">&times;</button></div>';
                echo '<form action="iniciar_pago.php" method="post" class="formulario-entrega">';
                echo '<input type="hidden" name="csrf_pago" value="' . $csrfPago . '">';
                echo '<label>Nombre de quien recibe<input name="nombre_entrega" type="text" maxlength="100" value="' . $nombreCuenta . '" autocomplete="name" required></label>';
                echo '<label>Correo electrónico<input name="correo_entrega" type="email" maxlength="100" value="' . $correoCuenta . '" autocomplete="email" required></label>';
                echo '<label>Teléfono de contacto<input name="telefono_entrega" type="tel" maxlength="30" autocomplete="tel" required></label>';
                echo '<label class="campo-direccion-completo">Dirección de entrega<input name="direccion_entrega" type="text" maxlength="200" autocomplete="street-address" placeholder="Calle, carrera, número, apartamento" required></label>';
                echo '<label>Ciudad o municipio<input name="ciudad_entrega" type="text" maxlength="80" autocomplete="address-level2" required></label>';
                echo '<label>Departamento<input name="departamento_entrega" type="text" maxlength="80" autocomplete="address-level1" required></label>';
                echo '<p class="aviso-datos-pago">Los datos de tarjeta se ingresan de forma segura en Stripe.</p>';
                echo '<button type="submit" class="btn-pagar">Realizar pago</button>';
                echo '</form></dialog>';
                echo '<script>const modalEntrega=document.getElementById("modal-entrega");document.getElementById("abrir-modal-entrega").addEventListener("click",()=>modalEntrega.showModal());document.getElementById("cerrar-modal-entrega").addEventListener("click",()=>modalEntrega.close());modalEntrega.addEventListener("click",event=>{if(event.target===modalEntrega)modalEntrega.close()});</script>';
            } catch (Throwable $error) {
                echo '<p>No fue posible preparar el inicio seguro del pago. Recarga el pedido para intentarlo de nuevo.</p>';
            }
        } else {
            echo '<div class="pedido-acciones"><a href="ver_carrito.php" class="pedido-volver">Volver al carrito</a></div>';
        }
    } else {
        echo '<a href="ver_carrito.php" class="btn-pagar">Volver al carrito</a>';
    }
    echo '</main></body></html>';
    exit();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: inicio.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ver_carrito.php');
    exit();
}

if (!stripeModoPruebaDisponible()) {
    responderEtapaPedido('El pago está temporalmente deshabilitado. Configura STRIPE_SECRET_KEY con una clave de prueba válida y vuelve a intentarlo.', 503);
}

$usuarioId = filter_var($_SESSION['usuario_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$carrito = $_SESSION['carrito'] ?? null;
if ($usuarioId === false || !is_array($carrito) || $carrito === []) {
    responderEtapaPedido('Inicia sesión con una cuenta válida y agrega productos antes de continuar.');
}

$validarUsuario = mysqli_prepare($conexion, "SELECT id FROM usuarios WHERE id = ? AND rol = 'usuario' AND activo = 1 LIMIT 1");
if (!$validarUsuario) {
    error_log('No se pudo preparar la validación del usuario al crear el pedido.');
    responderEtapaPedido('No fue posible validar tu cuenta. Inténtalo nuevamente.', 500);
}
mysqli_stmt_bind_param($validarUsuario, 'i', $usuarioId);
$usuarioConsultado = mysqli_stmt_execute($validarUsuario);
$resultadoUsuario = $usuarioConsultado ? mysqli_stmt_get_result($validarUsuario) : false;
$usuarioValido = $resultadoUsuario && mysqli_fetch_assoc($resultadoUsuario);
mysqli_stmt_close($validarUsuario);
if (!$usuarioValido) {
    responderEtapaPedido('La cuenta no está disponible para realizar pedidos.', 403);
}

$buscarPagoEnCurso = mysqli_prepare($conexion, "SELECT id, total, descuento, puntos_usados, referencia_pago FROM pedidos WHERE usuario_id = ? AND estado = 'procesando_pago' ORDER BY id DESC LIMIT 1");
if (!$buscarPagoEnCurso) {
    responderEtapaPedido('No fue posible comprobar si ya hay un pago en curso. Inténtalo nuevamente.', 500);
}
mysqli_stmt_bind_param($buscarPagoEnCurso, 'i', $usuarioId);
$pagoEnCursoConsultado = mysqli_stmt_execute($buscarPagoEnCurso);
$resultadoPagoEnCurso = $pagoEnCursoConsultado ? mysqli_stmt_get_result($buscarPagoEnCurso) : false;
$pagoEnCurso = $resultadoPagoEnCurso ? mysqli_fetch_assoc($resultadoPagoEnCurso) : null;
mysqli_stmt_close($buscarPagoEnCurso);
if (!$pagoEnCursoConsultado) {
    responderEtapaPedido('No fue posible comprobar si ya hay un pago en curso. Inténtalo nuevamente.', 500);
}
if ($pagoEnCurso) {
    $_SESSION['pago_pendiente'] = [
        'pedido_id' => (int) $pagoEnCurso['id'],
        'referencia' => (string) $pagoEnCurso['referencia_pago'],
        'monto' => (int) $pagoEnCurso['total'] * 100,
        'carrito' => $carrito,
    ];
    responderEtapaPedido('Este pedido ya está preparado. Revisa los productos y completa tus datos para continuar al pago.', 200, (int) $pagoEnCurso['id'], (string) $pagoEnCurso['referencia_pago'], (int) $pagoEnCurso['total'], true, (int) $pagoEnCurso['descuento'], (int) $pagoEnCurso['puntos_usados']);
}

$cantidadesCarrito = [];
foreach ($carrito as $nombre => $linea) {
    $nombre = trim((string) $nombre);
    $cantidad = is_array($linea) ? normalizarCantidadCarrito($linea['cantidad'] ?? null) : null;
    if ($nombre === '' || strlen($nombre) > 150 || $cantidad === null) {
        responderEtapaPedido('El carrito contiene un producto o una cantidad no válida. Revísalo antes de continuar.');
    }
    $cantidadesCarrito[$nombre] = $cantidad;
}
ksort($cantidadesCarrito, SORT_STRING);
$insertarPedido = mysqli_prepare($conexion, "INSERT INTO pedidos (usuario_id, total, descuento, puntos_usados, beneficio_solicitud_id, estado, referencia_pago) VALUES (?, ?, ?, ?, ?, 'pendiente', ?)");
$insertarDetalleProducto = mysqli_prepare($conexion, 'INSERT INTO detalles_pedido (pedido_id, producto_id, producto_nombre, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)');
$insertarDetalleOferta = mysqli_prepare($conexion, 'INSERT INTO detalles_pedido (pedido_id, producto_id, producto_nombre, cantidad, precio_unitario, subtotal) VALUES (?, NULL, ?, ?, ?, ?)');

if (!$insertarPedido || !$insertarDetalleProducto || !$insertarDetalleOferta) {
    foreach ([$insertarPedido, $insertarDetalleProducto, $insertarDetalleOferta] as $sentencia) {
        if ($sentencia) mysqli_stmt_close($sentencia);
    }
    error_log('No se pudieron preparar las consultas para crear un pedido pendiente.');
    responderEtapaPedido('No fue posible preparar el pedido. Inténtalo nuevamente.', 500);
}

if (!mysqli_begin_transaction($conexion)) {
    foreach ([$insertarPedido, $insertarDetalleProducto, $insertarDetalleOferta] as $sentencia) {
        mysqli_stmt_close($sentencia);
    }
    responderEtapaPedido('No fue posible iniciar el registro del pedido. Inténtalo nuevamente.', 500);
}
$bloquearUsuario = mysqli_prepare($conexion, 'SELECT id FROM usuarios WHERE id = ? FOR UPDATE');
if (!$bloquearUsuario) {
    mysqli_rollback($conexion);
    mysqli_stmt_close($insertarPedido);
    mysqli_stmt_close($insertarDetalleProducto);
    mysqli_stmt_close($insertarDetalleOferta);
    responderEtapaPedido('No fue posible reservar el pedido para evitar duplicados.', 500);
}
mysqli_stmt_bind_param($bloquearUsuario, 'i', $usuarioId);
$usuarioBloqueado = mysqli_stmt_execute($bloquearUsuario);
$resultadoUsuarioBloqueado = $usuarioBloqueado ? mysqli_stmt_get_result($bloquearUsuario) : false;
$filaUsuarioBloqueada = $resultadoUsuarioBloqueado ? mysqli_fetch_assoc($resultadoUsuarioBloqueado) : null;
mysqli_stmt_close($bloquearUsuario);
if (!$filaUsuarioBloqueada) {
    mysqli_rollback($conexion);
    mysqli_stmt_close($insertarPedido);
    mysqli_stmt_close($insertarDetalleProducto);
    mysqli_stmt_close($insertarDetalleOferta);
    responderEtapaPedido('No fue posible reservar el pedido para evitar duplicados.', 500);
}

$lineasPedido = [];
$carritoVerificado = [];
$totalCompra = 0;
$errorValidacion = '';

foreach ($cantidadesCarrito as $nombre => $cantidad) {
    $articulo = resolverArticuloCarrito($conexion, $nombre, true);
    if (!$articulo['valido']) {
        $errorValidacion = $articulo['error'];
        break;
    }
    if (!validarCantidadDisponible($articulo, $cantidad)) {
        $errorValidacion = 'La cantidad solicitada supera el inventario disponible de ' . $nombre . '.';
        break;
    }

    $productoId = $articulo['producto_id'];
    $precioUnitario = $articulo['precio'];
    $imagen = $articulo['imagen'];
    if ($precioUnitario < 0 || $precioUnitario > PEDIDO_MAXIMO_COP ||
        ($precioUnitario > 0 && $cantidad > intdiv(PEDIDO_MAXIMO_COP, $precioUnitario))) {
        $errorValidacion = 'El valor de uno de los artículos excede el límite permitido para un pedido.';
        break;
    }
    $subtotal = $precioUnitario * $cantidad;
    if ($subtotal > PEDIDO_MAXIMO_COP - $totalCompra) {
        $errorValidacion = 'El total del pedido excede el límite permitido.';
        break;
    }
    $totalCompra += $subtotal;
    $lineasPedido[] = [
        'producto_id' => $productoId,
        'producto_nombre' => $nombre,
        'cantidad' => $cantidad,
        'precio_unitario' => $precioUnitario,
        'subtotal' => $subtotal,
    ];
    $carritoVerificado[$nombre] = [
        'precio' => $precioUnitario,
        'cantidad' => $cantidad,
        'imagen' => $imagen,
    ];
}

$totalAPagar = $totalCompra;
$descuentoCompra = 0;
$puntosCanjeados = 0;
$pedidoSesionId = (int) ($_SESSION['pago_pendiente']['pedido_id'] ?? 0);
$buscarSolicitudDescuento = mysqli_prepare($conexion, "SELECT id, puntos, pedido_id, estado FROM solicitudes_beneficios WHERE usuario_id = ? AND tipo = 'descuento' AND (estado = 'solicitado' OR (estado = 'reservado' AND pedido_id = ?)) ORDER BY fecha_solicitud ASC, id ASC LIMIT 1 FOR UPDATE");
$solicitudDescuento = null;
if ($buscarSolicitudDescuento) {
    mysqli_stmt_bind_param($buscarSolicitudDescuento, 'ii', $usuarioId, $pedidoSesionId);
    if (mysqli_stmt_execute($buscarSolicitudDescuento)) {
        $resultadoSolicitudDescuento = mysqli_stmt_get_result($buscarSolicitudDescuento);
        $solicitudDescuento = $resultadoSolicitudDescuento ? mysqli_fetch_assoc($resultadoSolicitudDescuento) : null;
    }
    mysqli_stmt_close($buscarSolicitudDescuento);
}
if ($solicitudDescuento) {
    $puntosCanjeados = (int) $solicitudDescuento['puntos'];
    $puntosDisponiblesConSolicitud = puntosDisponiblesUsuario($conexion, $usuarioId) + $puntosCanjeados;
    if ($totalCompra > 50000 && $puntosCanjeados === 7 && $puntosDisponiblesConSolicitud >= $puntosCanjeados) {
        $descuentoCompra = (int) round($totalCompra * 0.30);
        $totalAPagar = $totalCompra - $descuentoCompra;
    } else {
        $puntosCanjeados = 0;
        $solicitudDescuento = null;
    }
}

if ($errorValidacion !== '' || $totalCompra <= 0 || $lineasPedido === []) {
    mysqli_rollback($conexion);
    mysqli_stmt_close($insertarPedido);
    mysqli_stmt_close($insertarDetalleProducto);
    mysqli_stmt_close($insertarDetalleOferta);
    responderEtapaPedido($errorValidacion !== '' ? $errorValidacion : 'El carrito debe tener un total mayor que cero.');
}

$huellaPrecios = [];
foreach ($lineasPedido as $linea) {
    $huellaPrecios[$linea['producto_nombre']] = [
        'cantidad' => $linea['cantidad'],
        'precio' => $linea['precio_unitario'],
    ];
}
ksort($huellaPrecios, SORT_STRING);
$huellaCarrito = hash('sha256', json_encode($huellaPrecios, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$pendienteSesion = $_SESSION['pago_pendiente'] ?? null;
if (is_array($pendienteSesion) &&
    isset($pendienteSesion['pedido_id'], $pendienteSesion['referencia'], $pendienteSesion['huella_carrito']) &&
        hash_equals($huellaCarrito, (string) $pendienteSesion['huella_carrito'])) {
    $pedidoIdAnterior = (int) $pendienteSesion['pedido_id'];
    $referenciaAnterior = (string) $pendienteSesion['referencia'];
    $buscarPedidoPendiente = mysqli_prepare($conexion, "SELECT total, descuento, puntos_usados, estado FROM pedidos WHERE id = ? AND usuario_id = ? AND referencia_pago = ? AND estado IN ('pendiente', 'procesando_pago') LIMIT 1 FOR UPDATE");
    if ($buscarPedidoPendiente) {
        mysqli_stmt_bind_param($buscarPedidoPendiente, 'iis', $pedidoIdAnterior, $usuarioId, $referenciaAnterior);
        $pedidoConsultado = mysqli_stmt_execute($buscarPedidoPendiente);
            $resultadoPedidoAnterior = $pedidoConsultado ? mysqli_stmt_get_result($buscarPedidoPendiente) : false;
        $pedidoAnterior = $resultadoPedidoAnterior ? mysqli_fetch_assoc($resultadoPedidoAnterior) : null;
        mysqli_stmt_close($buscarPedidoPendiente);
        if ($pedidoAnterior && (int) $pedidoAnterior['total'] === $totalAPagar && (int) $pedidoAnterior['descuento'] === $descuentoCompra && (int) $pedidoAnterior['puntos_usados'] === $puntosCanjeados) {
            mysqli_commit($conexion);
            $_SESSION['pago_pendiente']['monto'] = $totalAPagar * 100;
            $puedePagar = true;
            responderEtapaPedido(
                $puedePagar
                    ? 'Encontramos un pedido con estos productos y lo reutilizaremos para no duplicarlo.'
                    : 'Ya existe un pago iniciado para estos productos. El pedido se conserva mientras Stripe confirma el pago.',
                200,
                $pedidoIdAnterior,
                $referenciaAnterior,
                $totalAPagar,
                $puedePagar,
                $descuentoCompra,
                $puntosCanjeados
            );
        }
    }
}

$buscarPendientesIguales = mysqli_prepare($conexion, "SELECT id, total, descuento, puntos_usados, referencia_pago, estado FROM pedidos WHERE usuario_id = ? AND total = ? AND estado IN ('pendiente', 'procesando_pago') ORDER BY id DESC FOR UPDATE");
$buscarDetallesPendientes = mysqli_prepare($conexion, 'SELECT producto_nombre, cantidad, precio_unitario, subtotal FROM detalles_pedido WHERE pedido_id = ? ORDER BY id');
if (!$buscarPendientesIguales || !$buscarDetallesPendientes) {
    if ($buscarPendientesIguales) mysqli_stmt_close($buscarPendientesIguales);
    if ($buscarDetallesPendientes) mysqli_stmt_close($buscarDetallesPendientes);
    mysqli_rollback($conexion);
    mysqli_stmt_close($insertarPedido);
    mysqli_stmt_close($insertarDetalleProducto);
    mysqli_stmt_close($insertarDetalleOferta);
    responderEtapaPedido('No fue posible comprobar pedidos pendientes existentes.', 500);
}
mysqli_stmt_bind_param($buscarPendientesIguales, 'ii', $usuarioId, $totalAPagar);
$pendientesConsultados = mysqli_stmt_execute($buscarPendientesIguales);
$resultadoPendientes = $pendientesConsultados ? mysqli_stmt_get_result($buscarPendientesIguales) : false;
$pedidosPendientes = $resultadoPendientes ? mysqli_fetch_all($resultadoPendientes, MYSQLI_ASSOC) : [];
if ($resultadoPendientes) mysqli_free_result($resultadoPendientes);
mysqli_stmt_close($buscarPendientesIguales);
if (!$pendientesConsultados) {
    mysqli_stmt_close($buscarDetallesPendientes);
    mysqli_rollback($conexion);
    mysqli_stmt_close($insertarPedido);
    mysqli_stmt_close($insertarDetalleProducto);
    mysqli_stmt_close($insertarDetalleOferta);
    responderEtapaPedido('No fue posible comprobar pedidos pendientes existentes.', 500);
}

foreach ($pedidosPendientes as $pedidoPendiente) {
    $pedidoPendienteId = (int) $pedidoPendiente['id'];
    mysqli_stmt_bind_param($buscarDetallesPendientes, 'i', $pedidoPendienteId);
    $detallesConsultados = mysqli_stmt_execute($buscarDetallesPendientes);
    $resultadoDetallesPendientes = $detallesConsultados ? mysqli_stmt_get_result($buscarDetallesPendientes) : false;
    $detallesPendientes = $resultadoDetallesPendientes ? mysqli_fetch_all($resultadoDetallesPendientes, MYSQLI_ASSOC) : [];
    if ($resultadoDetallesPendientes) mysqli_free_result($resultadoDetallesPendientes);
    if (!$detallesConsultados) {
        mysqli_stmt_close($buscarDetallesPendientes);
        mysqli_rollback($conexion);
        mysqli_stmt_close($insertarPedido);
        mysqli_stmt_close($insertarDetalleProducto);
        mysqli_stmt_close($insertarDetalleOferta);
        responderEtapaPedido('No fue posible comprobar los detalles de pedidos pendientes.', 500);
    }
    if ($detallesPendientes === []) {
        continue;
    }
    $huellaDetalles = [];
    $totalDetallesPendientes = 0;
    foreach ($detallesPendientes as $detallePendiente) {
        $cantidadDetalle = (int) $detallePendiente['cantidad'];
        $precioDetalle = (int) $detallePendiente['precio_unitario'];
        $subtotalDetalle = (int) $detallePendiente['subtotal'];
        if ($cantidadDetalle < 1 || $precioDetalle < 0 || $subtotalDetalle !== $cantidadDetalle * $precioDetalle) {
            $huellaDetalles = [];
            break;
        }
        $nombreDetalle = (string) $detallePendiente['producto_nombre'];
        $huellaDetalles[$nombreDetalle] = ['cantidad' => $cantidadDetalle, 'precio' => $precioDetalle];
        $totalDetallesPendientes += $subtotalDetalle;
    }
    ksort($huellaDetalles, SORT_STRING);
    if ($huellaDetalles !== [] && $totalDetallesPendientes === $totalCompra &&
        (int) $pedidoPendiente['descuento'] === $descuentoCompra && (int) $pedidoPendiente['puntos_usados'] === $puntosCanjeados &&
        hash_equals($huellaCarrito, hash('sha256', json_encode($huellaDetalles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)))) {
        mysqli_stmt_close($buscarDetallesPendientes);
        mysqli_commit($conexion);
        $referenciaPendiente = (string) $pedidoPendiente['referencia_pago'];
        $_SESSION['pago_pendiente'] = [
            'pedido_id' => $pedidoPendienteId,
            'referencia' => $referenciaPendiente,
            'monto' => $totalAPagar * 100,
            'huella_carrito' => $huellaCarrito,
            'carrito' => $carritoVerificado,
        ];
        $puedePagar = true;
        responderEtapaPedido(
            $puedePagar
                ? 'Encontramos un pedido con estos productos y lo reutilizaremos para no duplicarlo.'
                : 'Ya existe un pago iniciado para estos productos. El pedido se conserva mientras Stripe confirma el pago.',
            200,
            $pedidoPendienteId,
            $referenciaPendiente,
            $totalAPagar,
            $puedePagar,
            $descuentoCompra,
            $puntosCanjeados
        );
    }
}
mysqli_stmt_close($buscarDetallesPendientes);

try {
    $referencia = 'AB-' . $usuarioId . '-' . bin2hex(random_bytes(12));
} catch (Throwable $error) {
    mysqli_rollback($conexion);
    mysqli_stmt_close($insertarPedido);
    mysqli_stmt_close($insertarDetalleProducto);
    mysqli_stmt_close($insertarDetalleOferta);
    error_log('No se pudo generar una referencia para el pedido.');
    responderEtapaPedido('No fue posible generar una referencia segura para el pedido.', 500);
}

$solicitudDescuentoId = $puntosCanjeados > 0 ? (int) $solicitudDescuento['id'] : null;
mysqli_stmt_bind_param($insertarPedido, 'iiiiis', $usuarioId, $totalAPagar, $descuentoCompra, $puntosCanjeados, $solicitudDescuentoId, $referencia);
$pedidoCreado = mysqli_stmt_execute($insertarPedido);
$pedidoId = $pedidoCreado ? (int) mysqli_insert_id($conexion) : 0;
mysqli_stmt_close($insertarPedido);

if ($pedidoCreado && $solicitudDescuentoId !== null) {
    $reservarSolicitud = mysqli_prepare($conexion, "UPDATE solicitudes_beneficios SET estado = 'reservado', pedido_id = ? WHERE id = ? AND usuario_id = ? AND tipo = 'descuento' AND estado = 'solicitado'");
    if ($reservarSolicitud) {
        mysqli_stmt_bind_param($reservarSolicitud, 'iii', $pedidoId, $solicitudDescuentoId, $usuarioId);
        $solicitudReservada = mysqli_stmt_execute($reservarSolicitud) && mysqli_stmt_affected_rows($reservarSolicitud) === 1;
        mysqli_stmt_close($reservarSolicitud);
    } else {
        $solicitudReservada = false;
    }
    if (!$solicitudReservada) {
        $pedidoCreado = false;
    }
}

if ($pedidoCreado && $pedidoId > 0) {
    foreach ($lineasPedido as $linea) {
        if ($linea['producto_id'] === null) {
            mysqli_stmt_bind_param(
                $insertarDetalleOferta,
                'isiii',
                $pedidoId,
                $linea['producto_nombre'],
                $linea['cantidad'],
                $linea['precio_unitario'],
                $linea['subtotal']
            );
            $detalleCreado = mysqli_stmt_execute($insertarDetalleOferta);
        } else {
            mysqli_stmt_bind_param(
                $insertarDetalleProducto,
                'iisiii',
                $pedidoId,
                $linea['producto_id'],
                $linea['producto_nombre'],
                $linea['cantidad'],
                $linea['precio_unitario'],
                $linea['subtotal']
            );
            $detalleCreado = mysqli_stmt_execute($insertarDetalleProducto);
        }
        if (!$detalleCreado) {
            $pedidoCreado = false;
            break;
        }
    }
}

mysqli_stmt_close($insertarDetalleProducto);
mysqli_stmt_close($insertarDetalleOferta);

if (!$pedidoCreado || !mysqli_commit($conexion)) {
    mysqli_rollback($conexion);
    error_log('No se pudo guardar de forma completa el pedido pendiente.');
    responderEtapaPedido('No fue posible guardar el pedido completo. El carrito se conservó; inténtalo nuevamente.', 500);
}

$_SESSION['pago_pendiente'] = [
    'pedido_id' => $pedidoId,
    'referencia' => $referencia,
    'monto' => $totalAPagar * 100,
    'huella_carrito' => $huellaCarrito,
    'carrito' => $carritoVerificado,
];

responderEtapaPedido('Estos son los productos elegidos. Revisa los productos y continúa cuando estés listo.', 200, $pedidoId, $referencia, $totalAPagar, true, $descuentoCompra, $puntosCanjeados);