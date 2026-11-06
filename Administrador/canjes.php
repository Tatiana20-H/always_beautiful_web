<?php
require_once __DIR__ . '/Seguridad.php';
require_once __DIR__ . '/../conexion.php';
$conexion = $GLOBALS['conexion'];
if (empty($_SESSION['csrf_canjes'])) {
    $_SESSION['csrf_canjes'] = bin2hex(random_bytes(32));
}
$aviso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_canjes'], (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('Solicitud no válida.');
    }
    $canjeId = (int) ($_POST['canje_id'] ?? 0);
    $accion = $_POST['accion'] ?? '';
    if ($canjeId > 0 && in_array($accion, ['entregado', 'cancelado'], true)) {
        $conexion->begin_transaction();
        $stmt = $conexion->prepare("SELECT usuario_id, producto_id, estado FROM canjes_puntos WHERE id = ? FOR UPDATE");
        $stmt->bind_param('i', $canjeId);
        $stmt->execute();
        $canje = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($canje && $canje['estado'] === 'solicitado') {
            $estadoNuevo = $accion;
            $stmt = $conexion->prepare('UPDATE canjes_puntos SET estado = ? WHERE id = ?');
            $stmt->bind_param('si', $estadoNuevo, $canjeId);
            $stmt->execute();
            $stmt->close();
            if ($accion === 'cancelado') {
                $stmt = $conexion->prepare('UPDATE usuarios SET puntos = puntos + 1 WHERE id = ?');
                $stmt->bind_param('i', $canje['usuario_id']);
                $stmt->execute();
                $stmt->close();
                $stmt = $conexion->prepare('UPDATE productos SET stock = stock + 1 WHERE id = ?');
                $stmt->bind_param('i', $canje['producto_id']);
                $stmt->execute();
                $stmt->close();
            }
            $conexion->commit();
            $aviso = $accion === 'entregado' ? 'Canje marcado como entregado.' : 'Canje cancelado; se devolvieron el punto y el stock.';
        } else {
            $conexion->rollback();
            $aviso = 'El canje ya no está pendiente.';
        }
    }
}

$canjes = $conexion->query('SELECT c.id, c.estado, c.fecha_creacion, u.nombre AS usuario, u.correo, p.nombre AS producto FROM canjes_puntos c JOIN usuarios u ON u.id = c.usuario_id JOIN productos p ON p.id = c.producto_id ORDER BY c.id ASC');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Canjes de puntos</title><link rel="stylesheet" href="../styles.css">
<style>
.canjes-admin{margin:28px auto;max-width:1200px;padding:0 24px}.canjes-admin h1{color:#481b18}.canjes-notice{background:#e6f1e8;border-radius:6px;color:#285d3b;margin:14px 0;padding:12px}.canjes-table-wrap{background:#fbf7f3;border:1px solid #d7b8ad;border-radius:8px;overflow-x:auto}.canjes-table{border-collapse:collapse;min-width:760px;width:100%}.canjes-table th{background:#743629;color:#fff;padding:12px;text-align:left}.canjes-table td{border-bottom:1px solid #e5d8ce;padding:12px}.canjes-action{background:#481b18;border:0;border-radius:5px;color:#fff;cursor:pointer;margin:2px;padding:8px 10px}.canjes-action.cancel{background:#7a3028}
</style>
</head>
<body>
<?php include __DIR__ . '/header-admin.php'; ?>
<main class="canjes-admin"><h1>Canjes de puntos</h1><?php if ($aviso !== ''): ?><p class="canjes-notice" role="status"><?= htmlspecialchars($aviso) ?></p><?php endif; ?>
<div class="canjes-table-wrap"><table class="canjes-table"><thead><tr><th>ID</th><th>Usuario</th><th>Correo</th><th>Obsequio</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr></thead><tbody>
<?php if ($canjes): while ($canje = $canjes->fetch_assoc()): ?><tr><td><?= (int) $canje['id'] ?></td><td><?= htmlspecialchars($canje['usuario']) ?></td><td><?= htmlspecialchars($canje['correo']) ?></td><td><?= htmlspecialchars($canje['producto']) ?></td><td><?= htmlspecialchars(ucfirst($canje['estado'])) ?></td><td><?= date('d/m/Y H:i', strtotime($canje['fecha_creacion'])) ?></td><td><?php if ($canje['estado'] === 'solicitado'): ?><form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_canjes']) ?>"><input type="hidden" name="canje_id" value="<?= (int) $canje['id'] ?>"><button class="canjes-action" name="accion" value="entregado">Marcar entregado</button><button class="canjes-action cancel" name="accion" value="cancelado" onclick="return confirm('¿Cancelar el canje y devolver el punto?')">Cancelar</button></form><?php else: ?>Sin acciones<?php endif; ?></td></tr><?php endwhile; endif; ?>
</tbody></table></div></main>
</body></html>
