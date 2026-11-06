<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/stripe_config.php';

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
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ver_carrito.php');
    exit();
}

if (empty($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    header('Location: ver_carrito.php');
    exit();
}

if (!stripeSecretKey()) {
    http_response_code(503);
    exit('Stripe no está configurado. Define STRIPE_SECRET_KEY en el entorno del servidor.');
}

$descuentos = [
    'Base Nars' => 20,
    'Paleta de sombras' => 15,
    'Protector solar' => 10,
];
$lineItems = [];
$carritoValidado = [];
$total = 0;

foreach ($_SESSION['carrito'] as $nombre => $item) {
    $nombre = trim((string) $nombre);
    $cantidad = filter_var($item['cantidad'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 99],
    ]);

    if ($nombre === '' || $cantidad === false) {
        http_response_code(400);
        exit('El carrito contiene un producto o una cantidad no válida.');
    }

    $stmt = $conexion->prepare('SELECT nombre, precio, stock, imagen FROM productos WHERE nombre = ? AND activo = 1 LIMIT 1');
    if (!$stmt) {
        http_response_code(500);
        exit('No se pudo consultar el catálogo de productos.');
    }
    $stmt->bind_param('s', $nombre);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$producto || (int) $producto['stock'] < $cantidad) {
        http_response_code(409);
        exit('Un producto ya no está disponible en la cantidad solicitada. Revisa tu carrito.');
    }

    $precio = (int) $producto['precio'];
    if (isset($descuentos[$producto['nombre']])) {
        $precio = (int) round($precio * (100 - $descuentos[$producto['nombre']]) / 100);
    }

    $lineItems[] = [
        'price_data' => [
            'currency' => 'cop',
            'unit_amount' => $precio,
            'product_data' => ['name' => $producto['nombre']],
        ],
        'quantity' => $cantidad,
    ];
    $carritoValidado[$producto['nombre']] = [
        'precio' => $precio,
        'cantidad' => $cantidad,
        'imagen' => $producto['imagen'] ?: 'IMG/default.jpg',
    ];
    $total += $precio * $cantidad;
}

if ($total <= 0) {
    http_response_code(400);
    exit('El total del pedido no es válido.');
}

$baseUrl = stripeBaseUrl();
if (!$baseUrl) {
    http_response_code(503);
    exit('Define APP_BASE_URL con la URL pública de este sitio para iniciar el pago.');
}

$checkout = stripeRequest('POST', '/v1/checkout/sessions', [
    'mode' => 'payment',
    'line_items' => $lineItems,
    'success_url' => $baseUrl . '/resultado_pago.php?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => $baseUrl . '/resultado_pago.php?cancel=1',
    'client_reference_id' => (string) $_SESSION['usuario_id'],
    'metadata' => ['usuario_id' => (string) $_SESSION['usuario_id']],
]);

if (!$checkout['success'] || empty($checkout['data']['id']) || empty($checkout['data']['url'])) {
    error_log('Stripe Checkout error: ' . ($checkout['error'] ?? 'respuesta inválida'));
    http_response_code(502);
    exit('Stripe no pudo iniciar el pago. Revisa la configuración e inténtalo de nuevo.');
}

$_SESSION['pago_pendiente'] = [
    'stripe_session_id' => $checkout['data']['id'],
    'total' => $total,
    'carrito' => $carritoValidado,
];

header('Location: ' . $checkout['data']['url'], true, 303);
exit();