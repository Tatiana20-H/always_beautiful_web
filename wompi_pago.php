<?php
function wompiCredencialesSandboxDisponibles() {
    return str_starts_with(WOMPI_PUBLIC_KEY, 'pub_test_')
        && str_starts_with(WOMPI_PRIVATE_KEY, 'prv_test_')
        && str_starts_with(WOMPI_INTEGRITY_SECRET, 'test_integrity_')
        && str_starts_with(WOMPI_EVENTS_SECRET, 'test_events_');
}

function wompiObtenerTransaccion($transaccionId) {
    if (!wompiCredencialesSandboxDisponibles() || !is_string($transaccionId) || $transaccionId === '' || !function_exists('curl_init')) {
        return null;
    }

    $curl = curl_init(wompiBaseUrl() . '/v1/transactions/' . rawurlencode($transaccionId));
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . WOMPI_PRIVATE_KEY],
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $respuesta = curl_exec($curl);
    $codigoHttp = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    if (!is_string($respuesta) || $codigoHttp !== 200) {
        return null;
    }

    $datos = json_decode($respuesta, true);
    $transaccion = $datos['data'] ?? null;
    return is_array($transaccion) ? $transaccion : null;
}

function wompiResolverRutaEvento(array $data, $ruta) {
    if (!is_string($ruta) || $ruta === '') {
        return null;
    }
    $valor = $data;
    foreach (explode('.', $ruta) as $segmento) {
        if (!is_array($valor) || !array_key_exists($segmento, $valor)) {
            return null;
        }
        $valor = $valor[$segmento];
    }
    if ($valor === null) {
        return '';
    }
    return is_scalar($valor) ? (string) $valor : null;
}

function wompiValidarEvento($cuerpo, $checksumHeader = '') {
    if (!wompiCredencialesSandboxDisponibles() || !is_string($cuerpo) || strlen($cuerpo) > 65536) {
        return null;
    }

    $evento = json_decode($cuerpo, true);
    if (!is_array($evento) || ($evento['event'] ?? '') !== 'transaction.updated' ||
        ($evento['environment'] ?? '') !== 'test' || !is_array($evento['data'] ?? null) ||
        !is_array($evento['signature'] ?? null) || !is_array($evento['signature']['properties'] ?? null) ||
        !is_numeric($evento['timestamp'] ?? null)) {
        return null;
    }

    $checksum = $evento['signature']['checksum'] ?? '';
    $propiedades = $evento['signature']['properties'];
    if (!is_string($checksum) || !preg_match('/^[a-f0-9]{64}$/i', $checksum) ||
        $propiedades === [] || count($propiedades) > 64) {
        return null;
    }

    $concatenado = '';
    foreach ($propiedades as $propiedad) {
        $valor = wompiResolverRutaEvento($evento['data'], $propiedad);
        if ($valor === null) {
            return null;
        }
        $concatenado .= $valor;
    }
    $concatenado .= (string) $evento['timestamp'] . WOMPI_EVENTS_SECRET;
    $checksumCalculado = hash('sha256', $concatenado);
    if (!hash_equals(strtolower($checksum), strtolower($checksumCalculado))) {
        return null;
    }
    if ($checksumHeader !== '' && (!is_string($checksumHeader) || !hash_equals(strtolower($checksum), strtolower($checksumHeader)))) {
        return null;
    }

    return $evento;
}

function wompiDetallesCoincidenConPedido($totalPedido, array $detalles, $descuentoPedido = 0) {
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

function wompiAplicarTransaccionVerificada($conexion, array $transaccion) {
    $transaccionId = $transaccion['id'] ?? null;
    $referencia = $transaccion['reference'] ?? null;
    $estadoTransaccion = $transaccion['status'] ?? null;
    $moneda = $transaccion['currency'] ?? null;
    $monto = filter_var($transaccion['amount_in_cents'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $estadosValidos = ['PENDING', 'APPROVED', 'DECLINED', 'VOIDED', 'ERROR'];

    if (!wompiCredencialesSandboxDisponibles() || !is_string($transaccionId) || $transaccionId === '' ||
        !is_string($referencia) || $referencia === '' || !in_array($estadoTransaccion, $estadosValidos, true) ||
        $moneda !== 'COP' || $monto === false) {
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
    $pedidoConsultado = mysqli_stmt_execute($buscarPedido);
    $resultadoPedido = $pedidoConsultado ? mysqli_stmt_get_result($buscarPedido) : false;
    $pedido = $resultadoPedido ? mysqli_fetch_assoc($resultadoPedido) : null;
    mysqli_stmt_close($buscarPedido);
    if (!$pedido) {
        mysqli_rollback($conexion);
        return false;
    }

    $pedidoId = (int) $pedido['id'];
    $totalPedido = (int) $pedido['total'];
    $transaccionGuardada = (string) ($pedido['transaccion_id'] ?? '');
    if ($monto !== $totalPedido * 100 || ($transaccionGuardada !== '' && $transaccionGuardada !== $transaccionId)) {
        mysqli_rollback($conexion);
        return false;
    }

    $buscarDetalles = mysqli_prepare($conexion, 'SELECT cantidad, precio_unitario, subtotal FROM detalles_pedido WHERE pedido_id = ?');
    if (!$buscarDetalles) {
        mysqli_rollback($conexion);
        return false;
    }
    mysqli_stmt_bind_param($buscarDetalles, 'i', $pedidoId);
    $detallesConsultados = mysqli_stmt_execute($buscarDetalles);
    $resultadoDetalles = $detallesConsultados ? mysqli_stmt_get_result($buscarDetalles) : false;
    $detalles = $resultadoDetalles ? mysqli_fetch_all($resultadoDetalles, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($buscarDetalles);
    if (!$detallesConsultados || !wompiDetallesCoincidenConPedido($totalPedido, $detalles, (int) $pedido['descuento'])) {
        mysqli_rollback($conexion);
        return false;
    }

    $estadoPedido = (string) $pedido['estado'];
    if (in_array($estadoPedido, ['completado', 'rechazado'], true) && $transaccionGuardada === $transaccionId) {
        return mysqli_commit($conexion);
    }
    if ($estadoPedido === 'procesando_pago' && $transaccionGuardada === $transaccionId && $estadoTransaccion === 'PENDING') {
        return mysqli_commit($conexion);
    }
    if (in_array($estadoPedido, ['completado', 'rechazado'], true)) {
        mysqli_rollback($conexion);
        return false;
    }
    if (!in_array($estadoPedido, ['pendiente', 'procesando_pago'], true)) {
        mysqli_rollback($conexion);
        return false;
    }

    $nuevoEstado = match ($estadoTransaccion) {
        'APPROVED' => 'completado',
        'DECLINED', 'VOIDED', 'ERROR' => 'rechazado',
        default => 'procesando_pago',
    };
    $metodoPago = is_string($transaccion['payment_method_type'] ?? null)
        ? substr($transaccion['payment_method_type'], 0, 40)
        : null;
    $actualizarPedido = mysqli_prepare($conexion, 'UPDATE pedidos SET estado = ?, transaccion_id = ?, metodo_pago = ? WHERE id = ? AND (transaccion_id IS NULL OR transaccion_id = ?)');
    if (!$actualizarPedido) {
        mysqli_rollback($conexion);
        return false;
    }
    mysqli_stmt_bind_param($actualizarPedido, 'sssis', $nuevoEstado, $transaccionId, $metodoPago, $pedidoId, $transaccionId);
    $actualizado = mysqli_stmt_execute($actualizarPedido);
    $filasAfectadas = mysqli_stmt_affected_rows($actualizarPedido);
    mysqli_stmt_close($actualizarPedido);
    if (!$actualizado || $filasAfectadas !== 1) {
        mysqli_rollback($conexion);
        return false;
    }

    if ($nuevoEstado === 'rechazado' && !liberarSolicitudDescuentoPedido($conexion, $pedidoId)) {
        mysqli_rollback($conexion);
        return false;
    }

    if ($nuevoEstado === 'completado' && !registrarPuntosPedido($conexion, (int) $pedido['usuario_id'], $pedidoId, $totalPedido, (int) $pedido['puntos_usados'], (int) $pedido['descuento'])) {
        mysqli_rollback($conexion);
        return false;
    }

    return mysqli_commit($conexion);
}
