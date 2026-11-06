<?php
function stripeUltimoErrorApi() {
    return (string) ($GLOBALS['stripe_ultimo_error_api'] ?? '');
}

function stripeSolicitudApi($metodo, $ruta, array $parametros = [], $idempotencyKey = null) {
    $GLOBALS['stripe_ultimo_error_api'] = '';
    if (!stripeModoPruebaDisponible() || !function_exists('curl_init')) {
        $GLOBALS['stripe_ultimo_error_api'] = 'Stripe no está disponible en el servidor.';
        return null;
    }

    $cabeceras = ['Authorization: Bearer ' . STRIPE_SECRET_KEY];
    if ($idempotencyKey !== null) {
        $cabeceras[] = 'Idempotency-Key: ' . $idempotencyKey;
    }
    $opciones = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $cabeceras,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_SSL_VERIFYPEER => true,
    ];
    $url = 'https://api.stripe.com/v1/' . ltrim($ruta, '/');
    if (strtoupper($metodo) === 'GET') {
        if ($parametros !== []) {
            $url .= '?' . http_build_query($parametros, '', '&', PHP_QUERY_RFC3986);
        }
    } else {
        $opciones[CURLOPT_POST] = true;
        $opciones[CURLOPT_POSTFIELDS] = http_build_query($parametros, '', '&', PHP_QUERY_RFC3986);
        $cabeceras[] = 'Content-Type: application/x-www-form-urlencoded';
        $opciones[CURLOPT_HTTPHEADER] = $cabeceras;
    }

    $respuesta = false;
    $codigoHttp = 0;
    $errorCurl = '';
    $errnoCurl = 0;
    for ($intento = 0; $intento < 2; $intento++) {
        $curl = curl_init($url);
        curl_setopt_array($curl, $opciones);
        $respuesta = curl_exec($curl);
        $codigoHttp = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $errorCurl = curl_error($curl);
        $errnoCurl = curl_errno($curl);
        curl_close($curl);
        if ($respuesta !== false || !in_array($errnoCurl, [CURLE_COULDNT_RESOLVE_HOST, CURLE_COULDNT_CONNECT, CURLE_OPERATION_TIMEDOUT, CURLE_SEND_ERROR, CURLE_RECV_ERROR], true)) {
            break;
        }
    }
    if (!is_string($respuesta) || $codigoHttp < 200 || $codigoHttp >= 300) {
        $datosError = is_string($respuesta) ? json_decode($respuesta, true) : null;
        $mensajeError = is_array($datosError) ? ($datosError['error']['message'] ?? '') : '';
        if (is_string($mensajeError) && $mensajeError !== '') {
            $GLOBALS['stripe_ultimo_error_api'] = 'Stripe respondió con HTTP ' . $codigoHttp . ': ' . substr($mensajeError, 0, 300);
        } elseif ($errorCurl !== '') {
            $GLOBALS['stripe_ultimo_error_api'] = 'No se pudo conectar con Stripe: ' . substr($errorCurl, 0, 200);
        } else {
            $GLOBALS['stripe_ultimo_error_api'] = 'Stripe no pudo crear el Checkout (HTTP ' . $codigoHttp . ').';
        }
        return null;
    }

    $datos = json_decode($respuesta, true);
    return is_array($datos) ? $datos : null;
}

function stripeCrearSesionCheckout($referencia, $pedidoId, $total, $urlBase, $correoCliente = '', $idempotencyKey = null) {
    $parametros = [
        'mode' => 'payment',
        'customer_email' => $correoCliente,
        'client_reference_id' => $referencia,
        'success_url' => rtrim($urlBase, '/') . '/resultado_pago.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => rtrim($urlBase, '/') . '/resultado_pago.php?cancelled=1',
        'line_items' => [[
            'price_data' => [
                'currency' => 'cop',
                'unit_amount' => (int) $total * 100,
                'product_data' => ['name' => 'Compra Always Beautiful'],
            ],
            'quantity' => 1,
        ]],
        'metadata' => [
            'pedido_id' => (string) (int) $pedidoId,
            'referencia_pago' => $referencia,
        ],
    ];
    $claveIdempotencia = $idempotencyKey ?? hash(
        'sha256',
        'stripe-checkout-' . json_encode($parametros, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
    return stripeSolicitudApi('POST', 'checkout/sessions', $parametros, $claveIdempotencia);
}

function stripeObtenerSesionCheckout($sessionId) {
    if (!is_string($sessionId) || !preg_match('/^cs_test_[A-Za-z0-9_]+$/D', $sessionId)) {
        return null;
    }
    return stripeSolicitudApi('GET', 'checkout/sessions/' . rawurlencode($sessionId));
}

function stripeDetallesCoincidenConPedido($totalPedido, array $detalles, $descuentoPedido = 0) {
    $suma = 0;
    if ($detalles === []) {
        return false;
    }
    foreach ($detalles as $detalle) {
        $cantidad = filter_var($detalle['cantidad'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $precio = filter_var($detalle['precio_unitario'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $subtotal = filter_var($detalle['subtotal'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($cantidad === false || $precio === false || $subtotal === false || $cantidad * $precio !== $subtotal || $subtotal > 4294967295 - $suma) {
            return false;
        }
        $suma += $subtotal;
    }
    return $suma === (int) $totalPedido + (int) $descuentoPedido;
}

function stripeDescontarStock($conexion, $productoId, $cantidad) {
    $actualizarStock = mysqli_prepare($conexion, 'UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?');
    if (!$actualizarStock) {
        return false;
    }
    mysqli_stmt_bind_param($actualizarStock, 'iii', $cantidad, $productoId, $cantidad);
    $ejecutado = mysqli_stmt_execute($actualizarStock);
    $descontado = $ejecutado && mysqli_stmt_affected_rows($actualizarStock) === 1;
    mysqli_stmt_close($actualizarStock);
    return $descontado;
}

function stripeAplicarSesionVerificada($conexion, array $sesion) {
    $sessionId = $sesion['id'] ?? null;
    $referencia = $sesion['client_reference_id'] ?? null;
    $monto = filter_var($sesion['amount_total'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $metadata = $sesion['metadata'] ?? [];
    if (!stripeModoPruebaDisponible() || !is_string($sessionId) || !preg_match('/^cs_test_[A-Za-z0-9_]+$/D', $sessionId) ||
        !is_string($referencia) || $referencia === '' || ($sesion['mode'] ?? null) !== 'payment' ||
        ($sesion['currency'] ?? null) !== 'cop' || ($sesion['payment_status'] ?? null) !== 'paid' ||
        $monto === false || !is_array($metadata) || ($metadata['referencia_pago'] ?? null) !== $referencia) {
        return false;
    }

    if (!mysqli_begin_transaction($conexion)) {
        return false;
    }
    $buscarPedido = mysqli_prepare($conexion, 'SELECT id, usuario_id, total, descuento, puntos_usados, estado, transaccion_id FROM pedidos WHERE referencia_pago = ? LIMIT 1 FOR UPDATE');
    if (!$buscarPedido) {
        mysqli_rollback($conexion);
        return false;
    }
    mysqli_stmt_bind_param($buscarPedido, 's', $referencia);
    $consultado = mysqli_stmt_execute($buscarPedido);
    $resultado = $consultado ? mysqli_stmt_get_result($buscarPedido) : false;
    $pedido = $resultado ? mysqli_fetch_assoc($resultado) : null;
    mysqli_stmt_close($buscarPedido);
    if (!$pedido || (int) $pedido['total'] * 100 !== $monto || (string) $pedido['id'] !== (string) ($metadata['pedido_id'] ?? '') ||
        !in_array($pedido['estado'], ['pendiente', 'procesando_pago', 'completado'], true) ||
        (!empty($pedido['transaccion_id']) && $pedido['transaccion_id'] !== $sessionId)) {
        mysqli_rollback($conexion);
        return false;
    }

    $pedidoId = (int) $pedido['id'];
    $totalPedido = (int) $pedido['total'];
    $buscarDetalles = mysqli_prepare($conexion, 'SELECT producto_id, producto_nombre, cantidad, precio_unitario, subtotal FROM detalles_pedido WHERE pedido_id = ? ORDER BY id');
    if (!$buscarDetalles) {
        mysqli_rollback($conexion);
        return false;
    }
    mysqli_stmt_bind_param($buscarDetalles, 'i', $pedidoId);
    $detallesConsultados = mysqli_stmt_execute($buscarDetalles);
    $resultadoDetalles = $detallesConsultados ? mysqli_stmt_get_result($buscarDetalles) : false;
    $detalles = $resultadoDetalles ? mysqli_fetch_all($resultadoDetalles, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($buscarDetalles);
    if (!$detallesConsultados || !stripeDetallesCoincidenConPedido((int) $pedido['total'], $detalles, (int) $pedido['descuento'])) {
        mysqli_rollback($conexion);
        return false;
    }
    if ($pedido['estado'] === 'completado') {
        return mysqli_commit($conexion);
    }

    if (!mysqli_query($conexion, 'SAVEPOINT stripe_pedido_stock')) {
        mysqli_rollback($conexion);
        return false;
    }
    $faltantesInventario = [];
    foreach ($detalles as $detalle) {
        $cantidad = (int) $detalle['cantidad'];
        $productoId = (int) ($detalle['producto_id'] ?? 0);
        if ($productoId > 0) {
            if (!stripeDescontarStock($conexion, $productoId, $cantidad)) {
                $faltantesInventario[] = (string) $detalle['producto_nombre'] . ' (solicitado: ' . $cantidad . ')';
            }
            continue;
        }

        $buscarCombo = mysqli_prepare($conexion, 'SELECT id FROM combos WHERE nombre = ? AND activo = 1 LIMIT 1');
        if (!$buscarCombo) {
            mysqli_rollback($conexion);
            return false;
        }
        $nombreCombo = (string) $detalle['producto_nombre'];
        mysqli_stmt_bind_param($buscarCombo, 's', $nombreCombo);
        $comboConsultado = mysqli_stmt_execute($buscarCombo);
        $resultadoCombo = $comboConsultado ? mysqli_stmt_get_result($buscarCombo) : false;
        $combo = $resultadoCombo ? mysqli_fetch_assoc($resultadoCombo) : null;
        mysqli_stmt_close($buscarCombo);
        if (!$combo) {
            continue;
        }
        $buscarComponentes = mysqli_prepare($conexion, 'SELECT producto_id, cantidad FROM combo_productos WHERE combo_id = ? ORDER BY producto_id');
        if (!$buscarComponentes) {
            mysqli_rollback($conexion);
            return false;
        }
        $comboId = (int) $combo['id'];
        mysqli_stmt_bind_param($buscarComponentes, 'i', $comboId);
        $componentesConsultados = mysqli_stmt_execute($buscarComponentes);
        $resultadoComponentes = $componentesConsultados ? mysqli_stmt_get_result($buscarComponentes) : false;
        $componentes = $resultadoComponentes ? mysqli_fetch_all($resultadoComponentes, MYSQLI_ASSOC) : [];
        mysqli_stmt_close($buscarComponentes);
        if (!$componentesConsultados) {
            mysqli_rollback($conexion);
            return false;
        }
        if (!mysqli_query($conexion, 'SAVEPOINT stripe_combo_stock')) {
            mysqli_rollback($conexion);
            return false;
        }
        $comboCompletoDisponible = true;
        foreach ($componentes as $componente) {
            $cantidadComponente = (int) $componente['cantidad'];
            if ($cantidadComponente < 1 || $cantidad > intdiv(PHP_INT_MAX, $cantidadComponente)) {
                mysqli_rollback($conexion);
                return false;
            }
            $cantidadNecesaria = $cantidad * $cantidadComponente;
            if (!stripeDescontarStock($conexion, (int) $componente['producto_id'], $cantidadNecesaria)) {
                $comboCompletoDisponible = false;
                $faltantesInventario[] = $nombreCombo . ' (componente #' . (int) $componente['producto_id'] . ', solicitado: ' . $cantidadNecesaria . ')';
            }
        }
        if (!$comboCompletoDisponible && !mysqli_query($conexion, 'ROLLBACK TO SAVEPOINT stripe_combo_stock')) {
            mysqli_rollback($conexion);
            return false;
        }
        if (!mysqli_query($conexion, 'RELEASE SAVEPOINT stripe_combo_stock')) {
            mysqli_rollback($conexion);
            return false;
        }
    }

    if ($faltantesInventario !== []) {
        if (!mysqli_query($conexion, 'ROLLBACK TO SAVEPOINT stripe_pedido_stock')) {
            mysqli_rollback($conexion);
            return false;
        }
    }
    if (!mysqli_query($conexion, 'RELEASE SAVEPOINT stripe_pedido_stock')) {
        mysqli_rollback($conexion);
        return false;
    }

    $observacionInventario = $faltantesInventario === []
        ? null
        : 'Pago confirmado, pero se requiere revisión de inventario: ' . implode('; ', $faltantesInventario);
    $actualizar = mysqli_prepare($conexion, "UPDATE pedidos SET estado = 'completado', transaccion_id = ?, metodo_pago = 'stripe', observacion_inventario = ?, fecha_pago = CURRENT_TIMESTAMP WHERE id = ? AND estado IN ('pendiente', 'procesando_pago') AND (transaccion_id IS NULL OR transaccion_id = ?)");
    if (!$actualizar) {
        mysqli_rollback($conexion);
        return false;
    }
    mysqli_stmt_bind_param($actualizar, 'ssis', $sessionId, $observacionInventario, $pedidoId, $sessionId);
    $ejecutado = mysqli_stmt_execute($actualizar);
    $afectadas = mysqli_stmt_affected_rows($actualizar);
    mysqli_stmt_close($actualizar);
    if (!$ejecutado || $afectadas !== 1) {
        mysqli_rollback($conexion);
        return false;
    }

    $detallesVenta = json_encode([
        'pedido_id' => $pedidoId,
        'referencia' => $referencia,
        'transaccion_id' => $sessionId,
        'total' => $totalPedido,
        'inventario' => $observacionInventario,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $descripcionVenta = 'Compra Stripe confirmada por $' . number_format($totalPedido, 0, ',', '.') . ' COP.';
    if ($observacionInventario !== null) {
        $descripcionVenta .= ' ' . $observacionInventario;
    }
    $ipCliente = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $registrarVenta = mysqli_prepare($conexion, "INSERT INTO historial_actividades (usuario_id, accion, descripcion, detalles, ip_address) VALUES (?, 'VENTA', ?, ?, ?)");
    if (!$registrarVenta || !is_string($detallesVenta)) {
        if ($registrarVenta) mysqli_stmt_close($registrarVenta);
        mysqli_rollback($conexion);
        return false;
    }
    $usuarioId = (int) $pedido['usuario_id'];
    mysqli_stmt_bind_param($registrarVenta, 'isss', $usuarioId, $descripcionVenta, $detallesVenta, $ipCliente);
    $ventaRegistrada = mysqli_stmt_execute($registrarVenta);
    mysqli_stmt_close($registrarVenta);
    if (!$ventaRegistrada) {
        mysqli_rollback($conexion);
        return false;
    }
    if (!registrarPuntosPedido($conexion, (int) $pedido['usuario_id'], $pedidoId, $totalPedido, (int) $pedido['puntos_usados'], (int) $pedido['descuento'])) {
        mysqli_rollback($conexion);
        return false;
    }
    return mysqli_commit($conexion);
}

function enviarFacturasPedido($conexion, $referencia) {
    $buscarPedido = mysqli_prepare($conexion, 'SELECT p.id, p.usuario_id, p.total, p.fecha_pago, p.fecha_pedido, p.datos_entrega, p.observacion_inventario, p.metodo_pago, p.correo_usuario_enviado, p.correo_admin_enviado, u.nombre AS nombre_cuenta, u.correo AS correo_cuenta FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id WHERE p.referencia_pago = ? AND p.estado = \'completado\' LIMIT 1');
    if (!$buscarPedido) {
        return false;
    }
    mysqli_stmt_bind_param($buscarPedido, 's', $referencia);
    $consultado = mysqli_stmt_execute($buscarPedido);
    $resultado = $consultado ? mysqli_stmt_get_result($buscarPedido) : false;
    $pedido = $resultado ? mysqli_fetch_assoc($resultado) : null;
    mysqli_stmt_close($buscarPedido);
    if (!$pedido) {
        return false;
    }

    $pedidoId = (int) $pedido['id'];
    $buscarDetalles = mysqli_prepare($conexion, 'SELECT producto_nombre, cantidad, precio_unitario, subtotal FROM detalles_pedido WHERE pedido_id = ? ORDER BY id');
    if (!$buscarDetalles) {
        return false;
    }
    mysqli_stmt_bind_param($buscarDetalles, 'i', $pedidoId);
    $detallesConsultados = mysqli_stmt_execute($buscarDetalles);
    $resultadoDetalles = $detallesConsultados ? mysqli_stmt_get_result($buscarDetalles) : false;
    $detalles = $resultadoDetalles ? mysqli_fetch_all($resultadoDetalles, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($buscarDetalles);
    if (!$detallesConsultados || $detalles === []) {
        return false;
    }

    $lineasFactura = [];
    foreach ($detalles as $detalle) {
        $lineasFactura[] = '- ' . (string) $detalle['producto_nombre'] .
            ' | Unidades: ' . (int) $detalle['cantidad'] .
            ' | Precio unitario: $' . number_format((int) $detalle['precio_unitario'], 0, ',', '.') . ' COP' .
            ' | Subtotal: $' . number_format((int) $detalle['subtotal'], 0, ',', '.') . ' COP';
    }
    $entrega = json_decode((string) ($pedido['datos_entrega'] ?? ''), true);
    $entrega = is_array($entrega) ? $entrega : [];
    $resumenEntrega = implode("\n", [
        'Recibe: ' . (string) ($entrega['nombre'] ?? $pedido['nombre_cuenta']),
        'Teléfono: ' . (string) ($entrega['telefono'] ?? 'No registrado'),
        'Dirección: ' . (string) ($entrega['direccion'] ?? 'No registrada'),
        'Ciudad: ' . (string) ($entrega['ciudad'] ?? 'No registrada'),
        'Departamento: ' . (string) ($entrega['departamento'] ?? 'No registrado'),
    ]);
    $fechaRegistroPago = (string) ($pedido['fecha_pago'] ?: $pedido['fecha_pedido']);
    $fechaCompra = new DateTimeImmutable($fechaRegistroPago, new DateTimeZone('America/Bogota'));
    $fechaCompra = $fechaCompra->setTimezone(new DateTimeZone('America/Bogota'));
    $metodoPagoRegistrado = trim((string) ($pedido['metodo_pago'] ?? ''));
    $metodoPago = strtolower($metodoPagoRegistrado) === 'stripe'
        ? 'Stripe'
        : 'Wompi' . ($metodoPagoRegistrado !== '' ? ' (' . $metodoPagoRegistrado . ')' : '');
    $cuerpoFactura = "Factura de compra - Always Beautiful\n\n" .
        'Pedido: ' . $pedidoId . "\n" .
        'Referencia: ' . $referencia . "\n\n" .
        "Productos facturados:\n" . implode("\n", $lineasFactura) . "\n\n" .
        'Total pagado: $' . number_format((int) $pedido['total'], 0, ',', '.') . " COP\n" .
        'Fecha y hora del pago: ' . $fechaCompra->format('d/m/Y H:i:s') . " (hora de Colombia)\n" .
        'Método de pago: ' . $metodoPago . "\n\n" .
        "Datos de entrega:\n" . $resumenEntrega . "\n\n" .
        "Gracias por tu compra.\nAlways Beautiful";

    $correoCliente = filter_var((string) ($entrega['correo'] ?? ''), FILTER_VALIDATE_EMAIL)
        ? (string) $entrega['correo']
        : (string) $pedido['correo_cuenta'];
    $usuarioEnviado = (int) $pedido['correo_usuario_enviado'] === 1;
    if (!$usuarioEnviado && $correoCliente !== '') {
        $usuarioEnviado = enviarNotificacion($correoCliente, 'Compra exitosa y factura - Always Beautiful', $cuerpoFactura);
        if ($usuarioEnviado) {
            $marcarUsuario = mysqli_prepare($conexion, 'UPDATE pedidos SET correo_usuario_enviado = 1 WHERE id = ? AND correo_usuario_enviado = 0');
            if ($marcarUsuario) {
                mysqli_stmt_bind_param($marcarUsuario, 'i', $pedidoId);
                mysqli_stmt_execute($marcarUsuario);
                mysqli_stmt_close($marcarUsuario);
            }
        }
    }

    $adminEnviado = (int) $pedido['correo_admin_enviado'] === 1;
    if (!$adminEnviado) {
        $cuerpoAdministrador = "Nueva venta confirmada en Always Beautiful\n\n" . $cuerpoFactura;
        if (!empty($pedido['observacion_inventario'])) {
            $cuerpoAdministrador .= "\n\nATENCIÓN DE INVENTARIO:\n" . (string) $pedido['observacion_inventario'];
        }
        $adminEnviado = enviarNotificacion(correoAdministradorNotificaciones(), 'Nueva venta confirmada - Pedido ' . $pedidoId, $cuerpoAdministrador);
        if ($adminEnviado) {
            $marcarAdmin = mysqli_prepare($conexion, 'UPDATE pedidos SET correo_admin_enviado = 1 WHERE id = ? AND correo_admin_enviado = 0');
            if ($marcarAdmin) {
                mysqli_stmt_bind_param($marcarAdmin, 'i', $pedidoId);
                mysqli_stmt_execute($marcarAdmin);
                mysqli_stmt_close($marcarAdmin);
            }
        }
    }
    return $usuarioEnviado && $adminEnviado;
}

function stripeValidarFirmaWebhook($cuerpo, $cabecera, $secreto) {
    if (!is_string($cuerpo) || strlen($cuerpo) > 65536 || !is_string($cabecera) || $secreto === '') {
        return null;
    }
    $timestamp = null;
    $firmas = [];
    foreach (explode(',', $cabecera) as $parte) {
        [$clave, $valor] = array_pad(explode('=', trim($parte), 2), 2, '');
        if ($clave === 't' && ctype_digit($valor)) {
            $timestamp = $valor;
        } elseif ($clave === 'v1' && preg_match('/^[a-f0-9]{64}$/i', $valor)) {
            $firmas[] = $valor;
        }
    }
    if ($timestamp === null || abs(time() - (int) $timestamp) > 300 || $firmas === []) {
        return null;
    }
    $firmaEsperada = hash_hmac('sha256', $timestamp . '.' . $cuerpo, $secreto);
    foreach ($firmas as $firma) {
        if (hash_equals($firmaEsperada, $firma)) {
            $evento = json_decode($cuerpo, true);
            return is_array($evento) ? $evento : null;
        }
    }
    return null;
}