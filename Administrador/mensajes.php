<?php 
include_once("Seguridad.php");
include_once("../conexion.php");
$conexion = $GLOBALS['conexion'];

if (empty($_SESSION['csrf_mensajes'])) {
    $_SESSION['csrf_mensajes'] = bin2hex(random_bytes(32));
}
$csrfMensajes = $_SESSION['csrf_mensajes'];

// Cambiar estado de mensaje a leído
if (isset($_POST['marcar_leido']) && !empty($_POST['mensaje_id'])) {
    $mensaje_id = intval($_POST['mensaje_id']);
    if (hash_equals($csrfMensajes, (string) ($_POST['csrf'] ?? ''))) {
        $stmtLeido = $conexion->prepare("UPDATE mensajes_contacto SET estado='leido' WHERE id = ? AND estado = 'no_leido'");
        $stmtLeido->bind_param('i', $mensaje_id);
        $stmtLeido->execute();
        $stmtLeido->close();
    }
}

// Obtener parámetros de filtro
$filtro = $_GET['filtro'] ?? 'todos';
$pagina = max(1, isset($_GET['pagina']) ? intval($_GET['pagina']) : 1);
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

// Construir consulta
$where = "";
if ($filtro === 'no_leido') {
    $where = "WHERE estado='no_leido'";
} elseif ($filtro === 'respondido') {
    $where = "WHERE estado='respondido'";
} else {
    $filtro = 'todos';
}

// Obtener total de mensajes
$resultado_total = mysqli_query($conexion, "SELECT COUNT(*) as total FROM mensajes_contacto $where");
$total = mysqli_fetch_assoc($resultado_total)['total'];
$paginas = ceil($total / $por_pagina);

// Obtener mensajes
$sql = "SELECT * FROM mensajes_contacto $where ORDER BY id ASC LIMIT $offset, $por_pagina";
$resultado = mysqli_query($conexion, $sql);

$no_leidos = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM mensajes_contacto WHERE estado='no_leido'"))['total'];
$tipoAvisoMensajes = isset($_SESSION['error']) ? 'error' : 'success';
$avisoMensajes = $_SESSION['exito'] ?? $_SESSION['error'] ?? '';
unset($_SESSION['exito'], $_SESSION['error']);
?>
<?php include("header-admin.php"); ?>

<style>
.mensajes-container {
    padding: 25px;
    background: #f8f9fa;
    min-height: 100vh;
}

.mensajes-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    background: white;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.mensajes-header h2 {
    color: #ffc107;
    margin: 0;
}

.badge-no-leidos {
    background: #dc3545;
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    font-weight: bold;
    margin-left: 10px;
}

.filtros {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.filtro-btn {
    padding: 10px 20px;
    border: 2px solid #ffc107;
    background: white;
    color: #ffc107;
    border-radius: 20px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s;
}

.filtro-btn.active {
    background: #ffc107;
    color: white;
}

.filtro-btn:hover {
    background: #d63384;
    color: white;
}

.mensajes-tabla {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.mensaje-item {
    border-bottom: 1px solid #e9ecef;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: background 0.3s;
}

.mensaje-item:hover {
    background: #f8f9fa;
}

.mensaje-item.no-leido {
    background: #fff9e6;
    border-left: 4px solid #ffc107;
}

.mensaje-info {
    flex: 1;
    cursor: pointer;
}

.mensaje-nombre {
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.mensaje-asunto {
    color: #555;
    margin-bottom: 5px;
}

.mensaje-correo {
    color: #999;
    font-size: 0.9em;
    margin-bottom: 5px;
}

.mensaje-fecha {
    color: #999;
    font-size: 0.85em;
}

.mensaje-acciones {
    display: flex;
    gap: 10px;
    margin-left: 20px;
}

.btn-ver, .btn-responder, .btn-eliminar {
    padding: 8px 15px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s;
    white-space: nowrap;
}

.btn-ver {
    background: #d63384;
    color: white;
}

.btn-ver:hover {
    background: #c5297a;
}

.btn-responder {
    background: #28a745;
    color: white;
}

.btn-responder:hover {
    background: #218838;
}

.btn-eliminar {
    background: #dc3545;
    color: white;
}

.btn-eliminar:hover {
    background: #c82333;
}

.estado-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: bold;
    margin-left: 10px;
}

.estado-no-leido {
    background: #ffc107;
    color: #333;
}

.estado-leido {
    background: #e9ecef;
    color: #555;
}

.estado-respondido {
    background: #28a745;
    color: white;
}

.paginacion {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.paginacion a, .paginacion span {
    padding: 10px 15px;
    border-radius: 8px;
    background: white;
    border: 1px solid #d63384;
    color: #d63384;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s;
}

.paginacion a:hover {
    background: #d63384;
    color: white;
}

.paginacion .activa {
    background: #d63384;
    color: white;
}

.sin-mensajes {
    text-align: center;
    padding: 50px;
    color: #999;
    background: white;
    border-radius: 15px;
}

.sin-mensajes h3 {
    color: #d63384;
    margin-bottom: 10px;
}

/* Modal estilos */
.modal {
    align-items: center;
    background: rgba(35, 25, 22, .62);
    display: none;
    inset: 0;
    justify-content: center;
    padding: 22px;
    position: fixed;
    z-index: 1000;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: #fffaf5;
    border: 1px solid #e6d3c7;
    border-radius: 12px;
    box-shadow: 0 24px 70px rgba(28, 17, 14, .3);
    max-height: min(86vh, 820px);
    max-width: 720px;
    overflow: auto;
    padding: 28px;
    width: min(100%, 720px);
}

.modal-content-respuesta { max-width: 620px; width: min(100%, 620px); }

.modal-header {
    align-items: flex-start;
    border-bottom: 1px solid #eadbd0;
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 16px;
}

.modal-header h3 {
    margin: 0;
    color: #481b18;
    font: 700 24px Georgia, "Times New Roman", serif;
}

.modal-kicker { color: #a65e3f; font-size: 11px; font-weight: 700; margin: 0 0 6px; text-transform: uppercase; }
.modal-subtitulo { color: #806d64; font-size: 13px; line-height: 1.5; margin: 6px 0 0; }
.modal-cerrar-icono { align-items: center; background: #f3e7dd; border: 0; border-radius: 50%; color: #481b18; cursor: pointer; display: inline-flex; flex: 0 0 36px; font-size: 22px; height: 36px; justify-content: center; line-height: 1; width: 36px; }
.modal-cerrar-icono:hover { background: #ead1c4; }

.modal-body {
    margin-bottom: 20px;
}

.detalle-metadatos { display: grid; gap: 12px; grid-template-columns: repeat(2, minmax(0, 1fr)); margin-bottom: 18px; }
.detalle-campo { min-width: 0; }

.detalle-campo label {
    display: block;
    color: #806d64;
    font-size: 11px;
    font-weight: 700;
    margin-bottom: 6px;
    text-transform: uppercase;
}

.detalle-campo p {
    background: #f7eee8;
    border: 1px solid #eadbd0;
    border-radius: 6px;
    color: #3f302b;
    line-height: 1.55;
    margin: 0;
    overflow-wrap: anywhere;
    padding: 10px 12px;
}

.detalle-mensaje { margin-bottom: 18px; }
.detalle-mensaje p { min-height: 88px; white-space: pre-wrap; }
.detalle-foto { background: #f7eee8; border: 1px solid #eadbd0; border-radius: 7px; display: flex; justify-content: center; margin: 0; overflow: hidden; padding: 10px; }
.detalle-foto img { display: block; max-height: 340px; max-width: 100%; object-fit: contain; }
.detalle-respuestas { border-top: 1px solid #eadbd0; margin-top: 20px; padding-top: 18px; }
.detalle-respuestas h4 { color: #481b18; font: 700 17px Georgia, "Times New Roman", serif; margin: 0 0 12px; }
.detalle-respuesta { background: #f7eee8; border-left: 3px solid #b87552; border-radius: 0 6px 6px 0; margin-bottom: 10px; padding: 12px 14px; }
.detalle-respuesta strong { color: #743629; display: block; font-size: 12px; margin-bottom: 7px; }
.detalle-respuesta p { background: transparent; border: 0; padding: 0; white-space: pre-wrap; }

.respuesta-destinatario { background: #f7eee8; border: 1px solid #eadbd0; border-radius: 7px; display: grid; gap: 4px; margin-bottom: 18px; padding: 12px 14px; }
.respuesta-destinatario span { color: #806d64; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.respuesta-destinatario strong { color: #481b18; font-size: 14px; overflow-wrap: anywhere; }
.respuesta-destinatario small { color: #806d64; font-size: 12px; overflow-wrap: anywhere; }
.respuesta-campo { display: grid; gap: 8px; }
.respuesta-campo label { color: #481b18; font-size: 13px; font-weight: 700; }
.respuesta-campo textarea { background: #fff; border: 1px solid #d9c6b8; border-radius: 6px; box-sizing: border-box; color: #3f302b; font: 15px/1.6 Georgia, "Times New Roman", serif; min-height: 190px; padding: 13px 14px; resize: vertical; width: 100%; }
.respuesta-campo textarea:focus { border-color: #a65e3f; box-shadow: 0 0 0 3px rgba(166, 94, 63, .14); outline: none; }
.respuesta-contador { color: #806d64; font-size: 11px; text-align: right; }

.modal-footer { align-items: center; border-top: 1px solid #eadbd0; display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px; padding-top: 16px; }
.modal-footer button { border-radius: 5px; cursor: pointer; font: 700 13px Georgia, "Times New Roman", serif; min-height: 40px; padding: 10px 16px; }
.modal-footer .btn-ver { background: #481b18; color: #fffaf5; }
.modal-footer .btn-ver:hover { background: #743629; }
.modal-footer .btn-responder { background: #481b18; color: #fffaf5; }
.modal-footer .btn-responder:hover { background: #743629; }

.cerrar-modal {
    background: transparent;
    border: 1px solid #b87552;
    color: #743629;
}

.cerrar-modal:hover {
    background: #f7eee8;
}

@media (max-width: 768px) {
    .mensaje-item {
        flex-direction: column;
        align-items: flex-start;
    }

    .mensaje-acciones {
        width: 100%;
        margin-left: 0;
        margin-top: 15px;
    }

    .btn-ver, .btn-responder, .btn-eliminar {
        flex: 1;
    }

    .mensajes-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .modal { padding: 12px; }
    .modal-content { border-radius: 9px; max-height: 92vh; padding: 20px 16px; }
    .modal-header h3 { font-size: 21px; }
    .detalle-metadatos { grid-template-columns: 1fr; }
    .modal-footer { flex-direction: column-reverse; }
    .modal-footer button { width: 100%; }
}
</style>

<div class="mensajes-container">
    <?php if ($avisoMensajes !== ''): ?><div class="catalogo-alerta <?= $tipoAvisoMensajes ?>" role="status"><?= htmlspecialchars($avisoMensajes) ?></div><?php endif; ?>
    <div class="mensajes-header">
        <h2>Mensajes de contacto <?php if ($no_leidos > 0): ?><span class="badge-no-leidos"><?php echo $no_leidos; ?> sin leer</span><?php endif; ?></h2>
    </div>

    <div class="filtros">
        <a href="?filtro=todos" class="filtro-btn <?php echo $filtro === 'todos' ? 'active' : ''; ?>">Todos (<?php echo mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM mensajes_contacto"))['total']; ?>)</a>
        <a href="?filtro=no_leido" class="filtro-btn <?php echo $filtro === 'no_leido' ? 'active' : ''; ?>">Sin leer (<?php echo $no_leidos; ?>)</a>
        <a href="?filtro=respondido" class="filtro-btn <?php echo $filtro === 'respondido' ? 'active' : ''; ?>">Respondidos</a>
    </div>

    <form id="csrfMensajesForm" hidden><input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfMensajes) ?>"></form>

    <?php if ($total > 0): ?>
        <div class="mensajes-tabla">
            <?php while ($mensaje = mysqli_fetch_assoc($resultado)): ?>
                <div class="mensaje-item <?php echo $mensaje['estado'] === 'no_leido' ? 'no-leido' : ''; ?>" onclick="abrirDetalle(<?php echo $mensaje['id']; ?>)">
                    <div class="mensaje-info">
                        <div class="mensaje-nombre">
                            <?php echo htmlspecialchars($mensaje['nombre']); ?>
                            <span class="estado-badge estado-<?php echo $mensaje['estado']; ?>">
                                <?php echo $mensaje['estado'] === 'no_leido' ? 'Sin leer' : ($mensaje['estado'] === 'respondido' ? 'Respondido' : 'Leído'); ?>
                            </span>
                        </div>
                        <div class="mensaje-asunto"><strong>Asunto:</strong> <?php echo htmlspecialchars($mensaje['asunto']); ?></div>
                        <div class="mensaje-correo"><strong>Correo:</strong> <?php echo htmlspecialchars($mensaje['correo']); ?></div>
                        <div class="mensaje-fecha"><?php echo date('d/m/Y H:i', strtotime($mensaje['fecha_creacion'])); ?></div>
                    </div>
                    <div class="mensaje-acciones" onclick="event.stopPropagation();">
                        <button class="btn-ver" onclick="abrirDetalle(<?php echo $mensaje['id']; ?>)">Ver</button>
                        <button class="btn-responder" onclick="abrirRespuesta(<?php echo $mensaje['id']; ?>)">Responder</button>
                        <button class="btn-eliminar" onclick="eliminarMensaje(<?php echo $mensaje['id']; ?>)">Eliminar</button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <?php if ($paginas > 1): ?>
            <div class="paginacion">
                <?php if ($pagina > 1): ?>
                    <a href="?filtro=<?php echo $filtro; ?>&pagina=1">Primera</a>
                    <a href="?filtro=<?php echo $filtro; ?>&pagina=<?php echo $pagina - 1; ?>">Anterior</a>
                <?php endif; ?>

                <?php for ($i = max(1, $pagina - 2); $i <= min($paginas, $pagina + 2); $i++): ?>
                    <?php if ($i == $pagina): ?>
                        <span class="activa"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?filtro=<?php echo $filtro; ?>&pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagina < $paginas): ?>
                    <a href="?filtro=<?php echo $filtro; ?>&pagina=<?php echo $pagina + 1; ?>">Siguiente</a>
                    <a href="?filtro=<?php echo $filtro; ?>&pagina=<?php echo $paginas; ?>">Última</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="sin-mensajes">
            <h3>No hay mensajes</h3>
            <p>No hay mensajes de contacto en esta categoría.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para ver detalles -->
<div id="modalDetalle" class="modal">
    <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="titulo-detalle-mensaje">
        <div class="modal-header">
            <div>
                <p class="modal-kicker">Bandeja de atención</p>
                <h3 id="titulo-detalle-mensaje">Detalle del mensaje</h3>
                <p class="modal-subtitulo">Información y conversación con el cliente.</p>
            </div>
            <button type="button" class="modal-cerrar-icono" aria-label="Cerrar detalle" onclick="document.getElementById('modalDetalle').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body" id="detalleBody"></div>
        <div class="modal-footer">
            <button class="btn-ver" onclick="document.getElementById('modalDetalle').classList.remove('active')">Cerrar detalle</button>
        </div>
    </div>
</div>

<!-- Modal para responder -->
<div id="modalRespuesta" class="modal">
    <div class="modal-content modal-content-respuesta" role="dialog" aria-modal="true" aria-labelledby="titulo-responder-mensaje">
        <div class="modal-header">
            <div>
                <p class="modal-kicker">Atención al cliente</p>
                <h3 id="titulo-responder-mensaje">Responder al cliente</h3>
                <p class="modal-subtitulo">Tu respuesta se guardará en la conversación y se enviará por correo.</p>
            </div>
            <button type="button" class="modal-cerrar-icono" aria-label="Cerrar respuesta" onclick="document.getElementById('modalRespuesta').classList.remove('active')">&times;</button>
        </div>
        <form id="formRespuesta" method="POST" action="procesar_respuesta.php">
            <div class="modal-body">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfMensajes) ?>">
                <input type="hidden" name="mensaje_id" id="mensajeIdRespuesta">
                <div class="respuesta-destinatario">
                    <span>Destinatario</span>
                    <strong id="respuesta-nombre">Cliente</strong>
                    <small id="respuesta-correo"></small>
                    <small id="respuesta-asunto"></small>
                </div>
                <div class="respuesta-campo">
                    <label for="texto-respuesta">Tu respuesta</label>
                    <textarea id="texto-respuesta" name="respuesta" rows="7" maxlength="5000" placeholder="Escribe una respuesta clara y útil..." required></textarea>
                    <span class="respuesta-contador"><span id="respuesta-caracteres">0</span> / 5000</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cerrar-modal" onclick="document.getElementById('modalRespuesta').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn-responder">Enviar respuesta</button>
            </div>
        </form>
    </div>
</div>

<script>
function escaparHtmlMensaje(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, caracter => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    })[caracter]);
}

async function abrirDetalle(mensajeId) {
    try {
        const response = await fetch('obtener_detalle_mensaje.php?id=' + mensajeId);
        const data = await response.json();
        
        if (data.success) {
            const nombreSeguro = escaparHtmlMensaje(data.nombre);
            let html = `
                <div class="detalle-campo">
                    <label>Nombre:</label>
                    <p>${escapeHtml(data.nombre)}</p>
                </div>
                <div class="detalle-campo">
                    <label>Correo:</label>
                    <p>${escapeHtml(data.correo)}</p>
                </div>
                <div class="detalle-campo">
                    <label>Asunto:</label>
                    <p>${escapeHtml(data.asunto)}</p>
                </div>
                <div class="detalle-campo">
                    <label>Mensaje:</label>
                    <p>${escapeHtml(data.mensaje)}</p>
                </div>
                ${data.foto_mensaje ? `<div class="detalle-campo"><label>Foto adjunta:</label><img class="foto-adjunta-detalle" src="../${escapeHtml(data.foto_mensaje)}" alt="Imagen adjunta al mensaje"></div>` : ''}
                <div class="detalle-campo">
                    <label>Fecha:</label>
                    <p>${data.fecha}</p>
                </div>
                ${data.foto_mensaje ? `<div class="detalle-campo"><label>Imagen adjunta</label><figure class="detalle-foto"><img src="../${escaparHtmlMensaje(data.foto_mensaje)}" alt="Imagen adjunta por ${nombreSeguro}"></figure></div>` : ''}
            `;

            if (data.respuestas && data.respuestas.length > 0) {
                html += '<section class="detalle-respuestas"><h4>Conversación</h4>';
                data.respuestas.forEach(respuesta => {
                    html += `
                        <div style="background: #f8f9fa; padding: 10px; border-radius: 8px; margin-bottom: 10px;">
                            <strong>Administrador - ${escapeHtml(respuesta.fecha)}</strong>
                            <p>${escapeHtml(respuesta.texto)}</p>
                        </div>
                    `;
                });
                html += '</section>';
            }

            document.getElementById('detalleBody').innerHTML = html;
            document.getElementById('modalDetalle').classList.add('active');
            
            // Marcar como leído
            const formData = new FormData();
            formData.append('marcar_leido', '1');
            formData.append('mensaje_id', mensajeId);
            formData.append('csrf', document.querySelector('#csrfMensajesForm [name="csrf"]').value);
            fetch('', { method: 'POST', body: formData });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[character]);
}

function abrirRespuesta(mensajeId) {
    document.getElementById('mensajeIdRespuesta').value = mensajeId;
    document.getElementById('respuesta-nombre').textContent = 'Cargando datos...';
    document.getElementById('respuesta-correo').textContent = '';
    document.getElementById('respuesta-asunto').textContent = '';
    document.getElementById('texto-respuesta').value = '';
    document.getElementById('respuesta-caracteres').textContent = '0';
    document.getElementById('modalRespuesta').classList.add('active');

    try {
        const response = await fetch('obtener_detalle_mensaje.php?id=' + encodeURIComponent(mensajeId));
        const data = await response.json();
        if (!data.success) throw new Error('No fue posible obtener los datos del mensaje.');
        document.getElementById('respuesta-nombre').textContent = data.nombre;
        document.getElementById('respuesta-correo').textContent = data.correo;
        document.getElementById('respuesta-asunto').textContent = 'Asunto: ' + data.asunto;
    } catch (error) {
        document.getElementById('respuesta-nombre').textContent = 'No se pudieron cargar los datos del cliente';
        console.error(error);
    }
}

document.getElementById('texto-respuesta').addEventListener('input', function () {
    document.getElementById('respuesta-caracteres').textContent = this.value.length;
});

function eliminarMensaje(mensajeId) {
    if (confirm('¿Estás seguro de que deseas eliminar este mensaje?')) {
        const formulario = document.createElement('form');
        formulario.method = 'POST';
        formulario.action = 'eliminar_mensaje.php';
        [['id', mensajeId], ['csrf', document.querySelector('#csrfMensajesForm [name="csrf"]').value]].forEach(([nombre, valor]) => {
            const campo = document.createElement('input');
            campo.type = 'hidden';
            campo.name = nombre;
            campo.value = valor;
            formulario.appendChild(campo);
        });
        document.body.appendChild(formulario);
        formulario.submit();
    }
}

// Cerrar modal al hacer click fuera
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});
</script>

</body>
</html>
