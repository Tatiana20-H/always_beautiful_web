<?php 
include("Seguridad.php");
include("../conexion.php");
$conexion = $GLOBALS['conexion'];
include("header-admin.php");

// Cambiar estado de mensaje a leído
if (isset($_POST['marcar_leido']) && !empty($_POST['mensaje_id'])) {
    $mensaje_id = intval($_POST['mensaje_id']);
    mysqli_query($conexion, "UPDATE mensajes_contacto SET estado='leido' WHERE id=$mensaje_id");
}

// Obtener parámetros de filtro
$filtro = $_GET['filtro'] ?? 'todos';
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

// Construir consulta
$where = "";
if ($filtro === 'no_leido') {
    $where = "WHERE estado='no_leido'";
} elseif ($filtro === 'respondido') {
    $where = "WHERE estado='respondido'";
}

// Obtener total de mensajes
$resultado_total = mysqli_query($conexion, "SELECT COUNT(*) as total FROM mensajes_contacto $where");
$total = mysqli_fetch_assoc($resultado_total)['total'];
$paginas = ceil($total / $por_pagina);

// Obtener mensajes
$sql = "SELECT * FROM mensajes_contacto $where ORDER BY fecha_creacion DESC LIMIT $offset, $por_pagina";
$resultado = mysqli_query($conexion, $sql);

$no_leidos = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM mensajes_contacto WHERE estado='no_leido'"))['total'];
?>

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
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: white;
    padding: 30px;
    border-radius: 15px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 5px 30px rgba(0,0,0,0.3);
}

.modal-header {
    margin-bottom: 20px;
    border-bottom: 2px solid #d63384;
    padding-bottom: 15px;
}

.modal-header h3 {
    margin: 0;
    color: #d63384;
}

.modal-body {
    margin-bottom: 20px;
}

.detalle-campo {
    margin-bottom: 15px;
}

.detalle-campo label {
    display: block;
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.detalle-campo p {
    margin: 0;
    color: #555;
    background: #f8f9fa;
    padding: 10px;
    border-radius: 8px;
    border-left: 3px solid #d63384;
}

.modal-footer {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.cerrar-modal {
    background: white;
    color: #d63384;
    border: 2px solid #d63384;
}

.cerrar-modal:hover {
    background: #f8f9fa;
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
}
</style>

<div class="mensajes-container">
    <div class="mensajes-header">
        <h2>📧 Mensajes de Contacto <?php if ($no_leidos > 0): ?><span class="badge-no-leidos"><?php echo $no_leidos; ?> sin leer</span><?php endif; ?></h2>
    </div>

    <div class="filtros">
        <a href="?filtro=todos" class="filtro-btn <?php echo $filtro === 'todos' ? 'active' : ''; ?>">Todos (<?php echo mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM mensajes_contacto"))['total']; ?>)</a>
        <a href="?filtro=no_leido" class="filtro-btn <?php echo $filtro === 'no_leido' ? 'active' : ''; ?>">Sin leer (<?php echo $no_leidos; ?>)</a>
        <a href="?filtro=respondido" class="filtro-btn <?php echo $filtro === 'respondido' ? 'active' : ''; ?>">Respondidos</a>
    </div>

    <?php if ($total > 0): ?>
        <div class="mensajes-tabla">
            <?php while ($mensaje = mysqli_fetch_assoc($resultado)): ?>
                <div class="mensaje-item <?php echo $mensaje['estado'] === 'no_leido' ? 'no-leido' : ''; ?>" onclick="abrirDetalle(<?php echo $mensaje['id']; ?>)">
                    <div class="mensaje-info">
                        <div class="mensaje-nombre">
                            <?php echo htmlspecialchars($mensaje['nombre']); ?>
                            <span class="estado-badge estado-<?php echo $mensaje['estado']; ?>">
                                <?php echo $mensaje['estado'] === 'no_leido' ? '📬 Sin leer' : ($mensaje['estado'] === 'respondido' ? '✅ Respondido' : '📖 Leído'); ?>
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
            <h3>📭 No hay mensajes</h3>
            <p>No hay mensajes de contacto en esta categoría.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para ver detalles -->
<div id="modalDetalle" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Detalles del Mensaje</h3>
        </div>
        <div class="modal-body" id="detalleBody"></div>
        <div class="modal-footer">
            <button class="btn-ver" onclick="document.getElementById('modalDetalle').classList.remove('active')">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal para responder -->
<div id="modalRespuesta" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Responder Mensaje</h3>
        </div>
        <form id="formRespuesta" method="POST" action="procesar_respuesta.php">
            <div class="modal-body">
                <input type="hidden" name="mensaje_id" id="mensajeIdRespuesta">
                <div class="detalle-campo">
                    <label>Respuesta:</label>
                    <textarea name="respuesta" rows="6" placeholder="Escribe tu respuesta..." required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-family: Arial;"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cerrar-modal" onclick="document.getElementById('modalRespuesta').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn-responder">Enviar Respuesta</button>
            </div>
        </form>
    </div>
</div>

<script>
async function abrirDetalle(mensajeId) {
    try {
        const response = await fetch('obtener_detalle_mensaje.php?id=' + mensajeId);
        const data = await response.json();
        
        if (data.success) {
            let html = `
                <div class="detalle-campo">
                    <label>Nombre:</label>
                    <p>${data.nombre}</p>
                </div>
                <div class="detalle-campo">
                    <label>Correo:</label>
                    <p>${data.correo}</p>
                </div>
                <div class="detalle-campo">
                    <label>Asunto:</label>
                    <p>${data.asunto}</p>
                </div>
                <div class="detalle-campo">
                    <label>Mensaje:</label>
                    <p>${data.mensaje}</p>
                </div>
                ${data.foto_mensaje ? `<div class="detalle-campo"><label>Foto adjunta:</label><img class="foto-adjunta-detalle" src="../${data.foto_mensaje}" alt="Foto adjunta al mensaje"></div>` : ''}
                <div class="detalle-campo">
                    <label>Fecha:</label>
                    <p>${data.fecha}</p>
                </div>
            `;

            if (data.respuestas && data.respuestas.length > 0) {
                html += '<div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #d63384;"><h4>Respuestas:</h4>';
                data.respuestas.forEach(respuesta => {
                    html += `
                        <div style="background: #f8f9fa; padding: 10px; border-radius: 8px; margin-bottom: 10px;">
                            <strong>Admin - ${respuesta.fecha}</strong>
                            <p>${respuesta.texto}</p>
                        </div>
                    `;
                });
                html += '</div>';
            }

            document.getElementById('detalleBody').innerHTML = html;
            document.getElementById('modalDetalle').classList.add('active');
            
            // Marcar como leído
            const formData = new FormData();
            formData.append('marcar_leido', '1');
            formData.append('mensaje_id', mensajeId);
            fetch('', { method: 'POST', body: formData });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function abrirRespuesta(mensajeId) {
    document.getElementById('mensajeIdRespuesta').value = mensajeId;
    document.getElementById('modalRespuesta').classList.add('active');
}

function eliminarMensaje(mensajeId) {
    if (confirm('¿Estás seguro de que deseas eliminar este mensaje?')) {
        window.location.href = 'eliminar_mensaje.php?id=' + mensajeId;
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
