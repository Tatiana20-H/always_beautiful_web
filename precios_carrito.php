<?php
function normalizarCantidadCarrito($valor) {
    if (is_int($valor) && $valor > 0) {
        return $valor;
    }
    if (is_string($valor) && preg_match('/^[1-9][0-9]*$/D', $valor)) {
        $cantidad = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $cantidad === false ? null : $cantidad;
    }
    return null;
}

function precioOfertaCarrito($precioBase, $descuento) {
    if (!is_numeric($precioBase) || !is_numeric($descuento)) {
        return null;
    }

    $precioBase = (float) $precioBase;
    $descuento = (float) $descuento;
    if (!is_finite($precioBase) || !is_finite($descuento) || $precioBase < 0 || $descuento < 0 || $descuento > 100) {
        return null;
    }

    $precio = round($precioBase * (100 - $descuento) / 100, 0, PHP_ROUND_HALF_UP);
    return $precio <= 4294967295 ? (int) $precio : null;
}

function resolverArticuloCarrito($conexion, $nombre, $bloquear = false) {
    $nombre = trim((string) $nombre);
    if ($nombre === '' || strlen($nombre) > 150) {
        return ['valido' => false, 'error' => 'El artículo del carrito no es válido.'];
    }

    $bloqueoSql = $bloquear ? ' FOR UPDATE' : '';
    $consultas = [
        'producto' => 'SELECT id, nombre, precio, stock, imagen FROM productos WHERE nombre = ? AND activo = 1 LIMIT 1' . $bloqueoSql,
        'promocion' => 'SELECT id, titulo AS nombre, precio, descuento, imagen FROM promociones WHERE titulo = ? AND activo = 1 LIMIT 2' . $bloqueoSql,
        'combo' => 'SELECT id, nombre, precio, descuento, imagen FROM combos WHERE nombre = ? AND activo = 1 LIMIT 2' . $bloqueoSql,
    ];
    $filas = [];

    foreach ($consultas as $tipo => $sql) {
        $sentencia = mysqli_prepare($conexion, $sql);
        if (!$sentencia) {
            return ['valido' => false, 'error' => 'No fue posible consultar el catálogo.'];
        }
        mysqli_stmt_bind_param($sentencia, 's', $nombre);
        $ejecutado = mysqli_stmt_execute($sentencia);
        $resultado = $ejecutado ? mysqli_stmt_get_result($sentencia) : false;
        if (!$ejecutado || !$resultado) {
            mysqli_stmt_close($sentencia);
            return ['valido' => false, 'error' => 'No fue posible consultar el catálogo.'];
        }
        $filas[$tipo] = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
        mysqli_free_result($resultado);
        mysqli_stmt_close($sentencia);
    }

    if (count($filas['promocion']) > 1 || count($filas['combo']) > 1 ||
        ($filas['promocion'] !== [] && $filas['combo'] !== [])) {
        return ['valido' => false, 'error' => 'El artículo coincide con varias ofertas activas. Contacta al administrador.'];
    }

    if ($filas['promocion'] !== [] || $filas['combo'] !== []) {
        $oferta = $filas['promocion'][0] ?? $filas['combo'][0];
        $precio = precioOfertaCarrito($oferta['precio'] ?? null, $oferta['descuento'] ?? null);
        if ($precio === null) {
            return ['valido' => false, 'error' => 'El precio o descuento de la oferta no es válido.'];
        }

        $productoRelacionado = $filas['promocion'] !== [] ? ($filas['producto'][0] ?? null) : null;
        $stockDisponible = $productoRelacionado ? (int) $productoRelacionado['stock'] : null;
        if ($filas['combo'] !== []) {
            $tablaComboProductos = mysqli_query($conexion, "SHOW TABLES LIKE 'combo_productos'");
            if ($tablaComboProductos && mysqli_num_rows($tablaComboProductos) > 0) {
                $sqlComponentes = 'SELECT cp.cantidad, p.stock, p.activo FROM combo_productos cp JOIN productos p ON p.id = cp.producto_id WHERE cp.combo_id = ?' . $bloqueoSql;
                $consultaComponentes = mysqli_prepare($conexion, $sqlComponentes);
                if (!$consultaComponentes) {
                    return ['valido' => false, 'error' => 'No fue posible validar el inventario del combo.'];
                }
                $comboId = (int) $oferta['id'];
                mysqli_stmt_bind_param($consultaComponentes, 'i', $comboId);
                $componentesConsultados = mysqli_stmt_execute($consultaComponentes);
                $resultadoComponentes = $componentesConsultados ? mysqli_stmt_get_result($consultaComponentes) : false;
                if (!$componentesConsultados || !$resultadoComponentes) {
                    mysqli_stmt_close($consultaComponentes);
                    return ['valido' => false, 'error' => 'No fue posible validar el inventario del combo.'];
                }
                $componentes = mysqli_fetch_all($resultadoComponentes, MYSQLI_ASSOC);
                mysqli_free_result($resultadoComponentes);
                mysqli_stmt_close($consultaComponentes);
                if ($componentes !== []) {
                    $stockDisponible = PHP_INT_MAX;
                    foreach ($componentes as $componente) {
                        $cantidadComponente = (int) $componente['cantidad'];
                        $stockComponente = (int) $componente['stock'];
                        if (!(int) $componente['activo'] || $cantidadComponente < 1) {
                            $stockDisponible = 0;
                            break;
                        }
                        $stockDisponible = min($stockDisponible, intdiv($stockComponente, $cantidadComponente));
                    }
                }
            }
            if ($tablaComboProductos) {
                mysqli_free_result($tablaComboProductos);
            }
        }
        return [
            'valido' => true,
            'tipo' => $filas['promocion'] !== [] ? 'promocion' : 'combo',
            'id' => (int) $oferta['id'],
            'producto_id' => $productoRelacionado ? (int) $productoRelacionado['id'] : null,
            'nombre' => (string) $oferta['nombre'],
            'precio' => $precio,
            'imagen' => (string) ($oferta['imagen'] ?? ''),
            'stock' => $stockDisponible,
        ];
    }

    if ($filas['producto'] === []) {
        return ['valido' => false, 'error' => 'El artículo ya no está disponible.'];
    }

    $producto = $filas['producto'][0];
    return [
        'valido' => true,
        'tipo' => 'producto',
        'id' => (int) $producto['id'],
        'producto_id' => (int) $producto['id'],
        'nombre' => (string) $producto['nombre'],
        'precio' => (int) $producto['precio'],
        'imagen' => (string) ($producto['imagen'] ?? ''),
        'stock' => (int) $producto['stock'],
    ];
}

function validarCantidadDisponible($articulo, $cantidad) {
    $cantidad = normalizarCantidadCarrito($cantidad);
    if ($cantidad === null) {
        return false;
    }
    return $articulo['stock'] === null || $cantidad <= $articulo['stock'];
}