<?php include("Seguridad.php"); ?>
<?php include("../conexion.php"); ?>
<?php $conexion = $GLOBALS['conexion']; ?>
<?php
$administradorId = (int) ($_SESSION['usuario_id'] ?? 0);
$directorioImagenes = realpath(__DIR__ . '/../IMG');
mysqli_query($conexion, "DELETE FROM galeria_inicio WHERE ruta LIKE 'IMG/perfiles/%'");
if ($directorioImagenes && is_dir($directorioImagenes)) {
    $imagenesExistentes = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directorioImagenes, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($imagenesExistentes as $archivoImagen) {
        if (!$archivoImagen->isFile() || !preg_match('/\.(jpe?g|png|gif|webp)$/i', $archivoImagen->getFilename())) {
            continue;
        }

        $rutaGaleria = 'IMG/' . str_replace(DIRECTORY_SEPARATOR, '/', substr($archivoImagen->getPathname(), strlen($directorioImagenes) + 1));
        if (strpos($rutaGaleria, 'IMG/perfiles/') === 0) {
            continue;
        }
        $nombreGaleria = $archivoImagen->getFilename();
        $verificarImagen = mysqli_prepare($conexion, "SELECT id FROM galeria_inicio WHERE ruta = ? LIMIT 1");
        if ($verificarImagen) {
            mysqli_stmt_bind_param($verificarImagen, "s", $rutaGaleria);
            mysqli_stmt_execute($verificarImagen);
            $resultadoImagen = mysqli_stmt_get_result($verificarImagen);
            if (!$resultadoImagen || mysqli_num_rows($resultadoImagen) === 0) {
                $insertarImagen = mysqli_prepare($conexion, "INSERT INTO galeria_inicio (nombre, ruta, administrador_id) VALUES (?, ?, ?)");
                if ($insertarImagen) {
                    mysqli_stmt_bind_param($insertarImagen, "ssi", $nombreGaleria, $rutaGaleria, $administradorId);
                    mysqli_stmt_execute($insertarImagen);
                    mysqli_stmt_close($insertarImagen);
                }
            }
            mysqli_stmt_close($verificarImagen);
        }
    }
}
?>
<?php
if (isset($_POST['guardar_imagen_galeria'])) {
    $archivoGaleria = $_FILES['imagen_galeria'] ?? null;
    $rutaGaleria = '';
    if ($archivoGaleria && ($archivoGaleria['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $carpetaGaleria = __DIR__ . '/../IMG/productos';
        if (!is_dir($carpetaGaleria)) {
            mkdir($carpetaGaleria, 0777, true);
        }
        $nombreArchivoGaleria = str_replace('.', '_', uniqid('', true)) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($archivoGaleria['name']));
        $destinoGaleria = $carpetaGaleria . '/' . $nombreArchivoGaleria;
        if (move_uploaded_file($archivoGaleria['tmp_name'], $destinoGaleria)) {
            $rutaGaleria = 'IMG/productos/' . $nombreArchivoGaleria;
            $nombreOriginalGaleria = basename($archivoGaleria['name']);
            $stmtImagenGaleria = mysqli_prepare($conexion, "INSERT INTO galeria_inicio (nombre, ruta, administrador_id) VALUES (?, ?, ?)");
            if ($stmtImagenGaleria) {
                mysqli_stmt_bind_param($stmtImagenGaleria, "ssi", $nombreOriginalGaleria, $rutaGaleria, $administradorId);
                mysqli_stmt_execute($stmtImagenGaleria);
                mysqli_stmt_close($stmtImagenGaleria);
            }
        }
    }
    header('Location: productos.php?galeria_abierta=1&imagen_subida=' . ($rutaGaleria !== '' ? '1' : '0'));
    exit;
}
include("header-admin.php");
?>

<style>
    .productos-container {
        padding: 30px;
        background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%);
        min-height: calc(100vh - 100px);
    }

    .productos-container h2 {
        color: #28a745;
        font-size: 2em;
        margin-bottom: 30px;
        font-weight: bold;
    }

    .formulario-producto {
        background: white;
        padding: 22px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        margin-bottom: 30px;
        max-width: 460px;
        flex: 1 1 0;
        min-width: 260px;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-self: flex-start;
    }

    .stats-admin {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 15px;
        margin-bottom: 24px;
    }

    .stat-admin {
        background: rgba(255,255,255,0.78);
        border: 1px solid rgba(40, 167, 69, 0.15);
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.05);
        padding: 16px 18px;
        backdrop-filter: blur(5px);
    }

    .stat-admin-label {
        display: block;
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        margin-bottom: 8px;
    }

    .stat-admin-valor {
        font-size: 2rem;
        font-weight: 700;
        color: #1f7a35;
    }

    .formulario-producto form {
        display: flex;
        flex-direction: column;
        height: auto;
        flex: 0 0 auto;
    }

    .formulario-producto input,
    .formulario-producto select,
    .formulario-producto button {
        width: 100%;
        box-sizing: border-box;
    }

    .formulario-producto form > input[type="file"] {
        display: none;
    }

    .formularios-fila {
        display: flex;
        gap: 20px;
        align-items: flex-start;
        flex-wrap: wrap;
        justify-content: space-between;
        margin-bottom: 30px;
    }

    .formulario-eliminar {
        max-width: 320px;
        flex: 1 1 260px;
    }

    .formulario-eliminar h3 {
        color: #9c3434;
        margin-top: 0;
    }

    .formulario-eliminar select {
        border: 1px solid #d8c8c0;
        border-radius: 8px;
        box-sizing: border-box;
        margin: 0 0 14px;
        padding: 12px;
        width: 100%;
    }

    .formulario-eliminar button {
        background: #9c3434;
        border: 0;
        border-radius: 8px;
        color: #fff;
        cursor: pointer;
        font-weight: 700;
        padding: 12px;
        width: 100%;
    }

    .galeria-miniatura {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(74px, 1fr));
        gap: 8px;
        max-height: 150px;
        overflow: auto;
        padding: 8px;
        background: #fffaf5;
        border: 1px solid #ead9ce;
        border-radius: 10px;
    }

    .galeria-overlay {
        align-items: center;
        background: rgba(72, 27, 24, .55);
        display: none;
        inset: 0;
        justify-content: center;
        padding: 20px;
        position: fixed;
        z-index: 1100;
    }

    .galeria-overlay.visible {
        display: flex;
    }

    .galeria-modal {
        background: #fffaf7;
        border-radius: 16px;
        max-height: min(620px, 90vh);
        max-width: 760px;
        overflow: auto;
        padding: 20px;
        position: relative;
        width: 100%;
    }

    .galeria-modal h3 {
        color: #481b18;
        margin: 0 32px 16px 0;
    }

    .galeria-subir {
        align-items: center;
        border-bottom: 1px solid #ead9ce;
        display: flex;
        gap: 10px;
        margin-bottom: 16px;
        padding-bottom: 16px;
    }

    .galeria-subir input[type="file"] {
        display: none;
    }

    .galeria-subir label {
        background: #ead9ce;
        border-radius: 8px;
        color: #481b18;
        cursor: pointer;
        flex: 1;
        overflow: hidden;
        padding: 10px 12px;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .galeria-subir button {
        background: #743629;
        border: 0;
        border-radius: 8px;
        color: #fff8f2;
        cursor: pointer;
        padding: 10px 14px;
        white-space: nowrap;
    }

    .selector-galeria {
        background: #743629 !important;
        margin: 0 0 15px !important;
        min-height: 40px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .ruta-imagen-seleccionada {
        display: none;
    }

    .cerrar-galeria {
        background: transparent !important;
        color: #743629 !important;
        font-size: 1.7rem !important;
        line-height: 1 !important;
        margin: 0 !important;
        padding: 4px 8px !important;
        position: absolute;
        right: 10px;
        top: 8px;
        width: auto !important;
    }

    .galeria-completa {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    }

    .galeria-productos-lista { display: grid; gap: 18px; }
    .galeria-productos-categoria { border-bottom: 1px solid #ead9ce; padding-bottom: 16px; }
    .galeria-productos-categoria:last-child { border-bottom: 0; padding-bottom: 0; }
    .galeria-productos-categoria h4 { color: #743629; font: 700 14px Georgia, "Times New Roman", serif; margin: 0 0 10px; }

    .galeria-completa figure {
        margin: 0;
    }

    .galeria-completa img {
        aspect-ratio: 1;
        border-radius: 10px;
        display: block;
        object-fit: cover;
        width: 100%;
    }

    .galeria-completa figure button {
        background: #743629;
        border: 0;
        border-radius: 7px;
        color: #fff8f2;
        cursor: pointer;
        font-size: .75rem;
        margin-top: 5px;
        padding: 7px;
        width: 100%;
    }

    .galeria-vacia {
        color: #765b52;
        margin: 0;
    }

    .formulario-producto form > button[type="submit"],
    .formulario-producto .abrir-preview {
        min-height: 40px;
    }

    .formulario-producto form > button[type="submit"] {
        margin-top: 12px;
    }

    .formulario-producto form > .selector-galeria {
        margin-bottom: 10px !important;
        margin-top: 0 !important;
    }

    .formulario-producto form > .selector-galeria + .ruta-imagen-seleccionada + button[type="submit"] {
        margin-top: 0;
    }

    @media (max-width: 600px) {
        .galeria-subir {
            align-items: stretch;
            flex-direction: column;
        }
    }

    .galeria-miniatura img {
        aspect-ratio: 1;
        border-radius: 7px;
        object-fit: cover;
        width: 100%;
    }

    .imagen-elegida-preview {
        aspect-ratio: 1;
        background: #f4f1ec;
        border: 1px solid #ddd;
        border-radius: 6px;
        box-sizing: border-box;
        display: none;
        height: 72px;
        margin: 0 0 8px;
        object-fit: contain;
        width: 72px;
    }

    .conteo-categorias {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin: -8px 0 24px;
    }

    .conteo-categorias span {
        background: #fff;
        border: 1px solid #d9e4dc;
        border-radius: 8px;
        color: #365344;
        padding: 8px 12px;
    }

    .editar-fila,
    .guardar-fila,
    .cancelar-fila {
        border: 0;
        border-radius: 6px;
        cursor: pointer;
        padding: 7px 10px;
        width: auto;
    }

    .editar-fila { background: #e8f2eb; color: #1f6735; }
    .guardar-fila { background: #23813d; color: #fff; display: none; }
    .cancelar-fila { background: #eee; color: #444; display: none; }
    .tabla-productos tr.editando .editar-fila { display: none; }
    .tabla-productos tr.editando .guardar-fila,
    .tabla-productos tr.editando .cancelar-fila { display: inline-block; }
    .tabla-productos .campo-edicion { display: none; max-width: 180px; width: 100%; }
    .tabla-productos tr.editando .valor-visible { display: none; }
    .tabla-productos tr.editando .campo-edicion { display: inline-block; }

    .abrir-preview {
        background: #b87552 !important;
        margin-top: 10px !important;
    }

    .formulario-producto h3 {
        color: #28a745;
        margin-top: 0;
    }

    .formulario-producto input {
        width: 100%;
        padding: 12px;
        margin-bottom: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-family: Arial;
        box-sizing: border-box;
        transition: border 0.3s;
    }

    .formulario-producto input:focus {
        outline: none;
        border-color: #28a745;
        box-shadow: 0 0 5px rgba(40, 167, 69, 0.3);
    }

    .formulario-producto button {
        width: 100%;
        background: linear-gradient(135deg, #28a745 0%, #34a954 100%);
        color: white;
        padding: 12px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        font-size: 1em;
        transition: all 0.3s;
        margin-top: auto;
    }

    .formulario-producto button:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
    }

    .vista-previa-overlay {
        background: rgba(72, 27, 24, .42);
        display: none;
        inset: 0;
        position: fixed;
        z-index: 1000;
    }

    .vista-previa-modal {
        left: 50%;
        max-height: calc(100vh - 40px);
        max-width: 300px;
        position: absolute;
        top: 50%;
        transform: translate(-50%, -50%);
        width: calc(100% - 32px);
    }

    .vista-previa-cabecera {
        align-items: center;
        background: #743629;
        border-radius: 16px 16px 0 0;
        color: #fff8f2;
        cursor: move;
        display: flex;
        justify-content: space-between;
        padding: 12px 16px;
    }

    .vista-previa-cabecera strong {
        font-size: .9rem;
    }

    .cerrar-preview {
        background: transparent !important;
        border: 0;
        color: #fff8f2;
        cursor: pointer;
        font-size: 1.5rem;
        line-height: 1;
        margin: 0 !important;
        padding: 0 !important;
        width: auto !important;
    }

    .vista-previa-modal .vista-previa-card {
        border-radius: 0 0 16px 16px;
        max-height: calc(100vh - 92px);
        overflow: auto;
    }

    .vista-previa-overlay.visible {
        display: block;
    }

    .vista-previa-overlay .vista-previa-card {
        background: #fffaf7;
        overflow: hidden;
        box-shadow: 0 12px 20px rgba(0,0,0,0.08);
    }

    .vista-previa-imagen {
        height: 112px;
        background: linear-gradient(135deg, #f2eee9 0%, #e6d5c5 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .vista-previa-imagen img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .vista-previa-contenido {
        padding: 11px;
    }

    .vista-previa-categoria {
        display: inline-block;
        background: #e0f2e8;
        color: #1f7a35;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.07em;
        padding: 5px 8px;
        border-radius: 999px;
        text-transform: uppercase;
    }

    .vista-previa-contenido h4 {
        margin: 8px 0 5px;
        color: #1e293b;
        font-size: 1.08rem;
    }

    .vista-previa-contenido p {
        color: #475569;
        margin: 0 0 12px;
        line-height: 1.35;
        font-size: 0.8rem;
    }

    .vista-previa-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .vista-previa-precio {
        font-weight: 700;
        color: #1e293b;
        font-size: 1rem;
    }

    .vista-previa-boton {
        background: #1f7a35;
        color: white;
        border: none;
        border-radius: 999px;
        padding: 8px 12px;
        font-weight: 600;
    }

    body.preview-abierto {
        overflow: hidden;
    }

    .tabla-productos {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }

    .tabla-productos table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .tabla-productos th {
        background: linear-gradient(135deg, #28a745 0%, #34a954 100%);
        color: white;
        padding: 20px;
        text-align: left;
        font-weight: 600;
        border: none;
    }

    .tabla-productos td {
        padding: 18px 20px;
        border-bottom: 1px solid #e9ecef;
        color: #555;
    }

    .tabla-productos tr:hover {
        background: #f8f9fa;
    }

    .tabla-productos tr:last-child td {
        border-bottom: none;
    }

    .alerta-exito {
        background: #d4edda;
        color: #155724;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        border-left: 4px solid #28a745;
    }

    .alerta-error {
        background: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        border-left: 4px solid #dc3545;
    }

    @media (max-width: 768px) {
        .productos-container {
            padding: 15px;
        }

        .formularios-fila {
            flex-direction: column;
        }

        .vista-previa-modal {
            max-width: 320px;
        }

    }
</style>

<div class="productos-container">
    <h2>Gestión de Productos</h2>

    <?php
    $total_promos = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM promociones"))['total'];
    $total_combos = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as total FROM combos"))['total'];
    $categoriasProducto = ['Piel' => 'Cuidado de piel', 'Cabello' => 'Cabello', 'Maquillaje' => 'Maquillaje', 'Accesorios' => 'Accesorios'];
    $conteosCategoria = array_fill_keys(array_keys($categoriasProducto), 0);
    $resultadoConteos = mysqli_query($conexion, "SELECT categoria, COUNT(*) AS total FROM productos GROUP BY categoria");
    if ($resultadoConteos) {
        while ($conteoCategoria = mysqli_fetch_assoc($resultadoConteos)) {
            if (array_key_exists($conteoCategoria['categoria'], $conteosCategoria)) {
                $conteosCategoria[$conteoCategoria['categoria']] = (int) $conteoCategoria['total'];
            }
        }
    }
    ?>

    <div class="stats-admin">
        <div class="stat-admin">
            <span class="stat-admin-label">Productos</span>
            <span class="stat-admin-valor"><?php echo mysqli_num_rows(mysqli_query($conexion, "SELECT * FROM productos")); ?></span>
        </div>
        <div class="stat-admin">
            <span class="stat-admin-label">Promociones</span>
            <span class="stat-admin-valor"><?php echo $total_promos; ?></span>
        </div>
        <div class="stat-admin">
            <span class="stat-admin-label">Combos</span>
            <span class="stat-admin-valor"><?php echo $total_combos; ?></span>
        </div>
    </div>

    <div class="conteo-categorias" aria-label="Productos por categoría">
        <?php foreach ($categoriasProducto as $claveCategoria => $nombreCategoria): ?>
            <span><?php echo htmlspecialchars($nombreCategoria); ?>: <strong><?php echo $conteosCategoria[$claveCategoria]; ?></strong></span>
        <?php endforeach; ?>
    </div>

    <div class="formularios-fila">
        <div class="formulario-producto">
            <h3>Agregar Nuevo Producto</h3>
            <form method="POST" enctype="multipart/form-data" id="form-producto">
                <input type="text" name="nombre" id="producto-nombre" placeholder="Nombre del producto" required>
                <input type="text" name="precio" id="producto-precio" placeholder="Precio (ej. 169.000)" inputmode="numeric" pattern="[0-9.,]+" required>
                <input type="number" name="stock" id="producto-stock" placeholder="Stock disponible" min="0" required>
                <input type="text" name="descripcion" id="producto-descripcion" placeholder="Descripción del producto">
                <select name="categoria" id="producto-categoria" required>
                    <option value="">Selecciona una categoría</option>
                    <option value="Piel">Cuidado de piel</option>
                    <option value="Cabello">Cabello</option>
                    <option value="Maquillaje">Maquillaje</option>
                    <option value="Accesorios">Accesorios</option>
                </select>
                <input type="file" name="imagen" id="producto-imagen" accept="image/*">
                <input type="hidden" name="imagen_galeria_ruta" id="producto-imagen-ruta">
                <button class="selector-galeria" id="buscar-imagen-producto" type="button">Buscar imagen en el equipo</button>
                <img class="imagen-elegida-preview" id="producto-imagen-elegida" alt="Imagen del producto seleccionada">
                <span class="ruta-imagen-seleccionada" id="producto-imagen-preview">Ninguna imagen seleccionada</span>
                <button type="submit" name="guardar">Guardar Producto</button>
            </form>
        </div>

        <div class="formulario-producto formulario-eliminar">
            <h3>Eliminar producto, promoción o combo</h3>
            <form method="POST" data-confirm-delete>
                <select name="elemento_eliminar" required aria-label="Título del elemento">
                    <option value="">Selecciona un título</option>
                    <?php
                    $elementosEliminar = [
                        'Producto' => ['tabla' => 'productos', 'tipo' => 'producto', 'columna' => 'nombre'],
                        'Promoción' => ['tabla' => 'promociones', 'tipo' => 'promocion', 'columna' => 'titulo'],
                        'Combo' => ['tabla' => 'combos', 'tipo' => 'combo', 'columna' => 'nombre'],
                    ];
                    foreach ($elementosEliminar as $grupoEliminar => $configuracionEliminar):
                        $resultadoEliminar = mysqli_query($conexion, "SELECT id, {$configuracionEliminar['columna']} AS titulo FROM {$configuracionEliminar['tabla']} ORDER BY {$configuracionEliminar['columna']} ASC");
                        if ($resultadoEliminar && mysqli_num_rows($resultadoEliminar) > 0):
                    ?>
                        <optgroup label="<?php echo htmlspecialchars($grupoEliminar, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php while ($elementoEliminar = mysqli_fetch_assoc($resultadoEliminar)): ?>
                                <option value="<?php echo $configuracionEliminar['tipo'] . ':' . (int) $elementoEliminar['id']; ?>"><?php echo htmlspecialchars($elementoEliminar['titulo'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endwhile; ?>
                        </optgroup>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </select>
                <button type="submit" name="eliminar_elemento" value="1">Eliminar</button>
            </form>
        </div>

        <div class="formulario-producto">
            <h3>Agregar Promoción o Combo</h3>
            <form method="POST" enctype="multipart/form-data" id="form-promocion-combo">
                <select name="oferta_tipo" id="oferta-tipo" required>
                    <option value="promocion">Promoción</option>
                    <option value="combo">Combo</option>
                </select>
                <input type="text" name="oferta_titulo" id="oferta-titulo" placeholder="Título de la promoción o combo" required>
                <input type="text" name="oferta_descripcion" id="oferta-descripcion" placeholder="Descripción" required>
                <input type="text" name="oferta_productos" id="oferta-productos" placeholder="Productos incluidos, separados por coma (opcional)">
                <select name="oferta_categoria" id="oferta-categoria" required>
                    <option value="">Selecciona una categoría</option>
                    <option value="Piel">Cuidado de piel</option>
                    <option value="Cabello">Cabello</option>
                    <option value="Maquillaje">Maquillaje</option>
                    <option value="Accesorios">Accesorios</option>
                </select>
                <input type="text" name="oferta_precio" id="oferta-precio" placeholder="Precio en COP (ej. 140.000)" inputmode="numeric" pattern="[0-9.,]+" required>
                <input type="number" name="oferta_descuento" id="oferta-descuento" placeholder="Descuento (%)" min="0" max="100" step="0.01" value="0" required>
                <input type="hidden" name="imagen_galeria_ruta" id="oferta-imagen-ruta">
                <button class="selector-galeria" id="buscar-imagen-oferta" type="button">Buscar imagen en el equipo</button>
                <input type="file" name="oferta_imagen" id="oferta-imagen" accept="image/*">
                <img class="imagen-elegida-preview" id="oferta-imagen-elegida" alt="Imagen de la promoción o combo seleccionada">
                <span class="ruta-imagen-seleccionada" id="oferta-imagen-preview">Ninguna imagen seleccionada</span>
                <button type="submit" name="guardar_oferta">Guardar promoción o combo</button>
            </form>
        </div>

    </div>

    <div class="galeria-overlay" id="galeria-overlay" role="dialog" aria-modal="true" aria-labelledby="titulo-galeria">
        <div class="galeria-modal">
            <button class="cerrar-galeria" id="cerrar-galeria" type="button" aria-label="Cerrar galería">x</button>
            <h3 id="titulo-galeria">Galería de imágenes subidas</h3>
            <form class="galeria-subir" id="form-subir-galeria" action="productos.php" method="POST" enctype="multipart/form-data">
                <input type="file" name="imagen_galeria" id="imagen-galeria-subir" accept="image/*" required>
                <label for="imagen-galeria-subir" id="nombre-imagen-galeria">Seleccionar imagen nueva</label>
                <button type="submit" name="guardar_imagen_galeria">Subir imagen</button>
            </form>
            <div class="galeria-completa">
                <?php
                $imagenesGaleria = mysqli_query($conexion, "SELECT nombre, ruta FROM galeria_inicio WHERE ruta LIKE 'IMG/productos/%' ORDER BY fecha_creacion DESC LIMIT 100");
                if ($imagenesGaleria && mysqli_num_rows($imagenesGaleria) > 0):
                    while ($imagenGaleria = mysqli_fetch_assoc($imagenesGaleria)):
                ?>
                        <figure>
                            <img src="../<?php echo htmlspecialchars($imagenGaleria['ruta']); ?>" alt="<?php echo htmlspecialchars($imagenGaleria['nombre']); ?>">
                            <button type="button" class="usar-imagen-galeria" data-ruta="<?php echo htmlspecialchars($imagenGaleria['ruta']); ?>" data-nombre="<?php echo htmlspecialchars($imagenGaleria['nombre']); ?>">Seleccionar</button>
                        </figure>
                <?php
                    endwhile;
                else:
                ?>
                    <p class="galeria-vacia">Todavía no hay imágenes subidas.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="galeria-overlay" id="galeria-productos-overlay" role="dialog" aria-modal="true" aria-labelledby="titulo-galeria-productos">
        <div class="galeria-modal">
            <button class="cerrar-galeria" id="cerrar-galeria-productos" type="button" aria-label="Cerrar selector de imágenes">x</button>
            <h3 id="titulo-galeria-productos">Imágenes de productos existentes</h3>
            <?php
            $categoriasImagenesOferta = [
                'Maquillaje' => 'Maquillaje',
                'Piel' => 'Cuidado de piel',
                'Cabello' => 'Cabello',
                'Accesorios' => 'Accesorios',
            ];
            $imagenesProductosPorCategoria = array_fill_keys(array_keys($categoriasImagenesOferta), []);
            $imagenesProductosExistentes = mysqli_query($conexion, "SELECT nombre, categoria, imagen FROM productos WHERE activo = 1 AND imagen IS NOT NULL AND imagen <> '' ORDER BY nombre ASC");
            if ($imagenesProductosExistentes) {
                while ($productoConImagen = mysqli_fetch_assoc($imagenesProductosExistentes)) {
                    $categoriaImagen = (string) ($productoConImagen['categoria'] ?? '');
                    $rutaImagenProducto = trim((string) $productoConImagen['imagen']);
                    $rutaRealProducto = strpos($rutaImagenProducto, 'IMG/') === 0 && strpos($rutaImagenProducto, '..') === false
                        ? realpath(__DIR__ . '/../' . $rutaImagenProducto)
                        : false;
                    $directorioImagenesProyecto = realpath(__DIR__ . '/../IMG');
                    if (!isset($imagenesProductosPorCategoria[$categoriaImagen]) || !$rutaRealProducto || !$directorioImagenesProyecto || strpos($rutaRealProducto, $directorioImagenesProyecto . DIRECTORY_SEPARATOR) !== 0) {
                        continue;
                    }
                    $imagenesProductosPorCategoria[$categoriaImagen][] = [
                        'nombre' => $productoConImagen['nombre'],
                        'ruta' => $rutaImagenProducto,
                    ];
                }
            }
            ?>
            <div class="galeria-productos-lista">
                <?php $hayImagenesProductos = false; ?>
                <?php foreach ($categoriasImagenesOferta as $categoriaImagen => $nombreCategoriaImagen): ?>
                    <?php if ($imagenesProductosPorCategoria[$categoriaImagen]): $hayImagenesProductos = true; ?>
                        <section class="galeria-productos-categoria" aria-label="<?= htmlspecialchars($nombreCategoriaImagen, ENT_QUOTES, 'UTF-8') ?>">
                            <h4><?= htmlspecialchars($nombreCategoriaImagen, ENT_QUOTES, 'UTF-8') ?></h4>
                            <div class="galeria-completa">
                                <?php foreach ($imagenesProductosPorCategoria[$categoriaImagen] as $productoConImagen): ?>
                                    <figure>
                                        <img src="../<?= htmlspecialchars($productoConImagen['ruta'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($productoConImagen['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="button" class="usar-imagen-producto" data-ruta="<?= htmlspecialchars($productoConImagen['ruta'], ENT_QUOTES, 'UTF-8') ?>" data-nombre="<?= htmlspecialchars($productoConImagen['nombre'], ENT_QUOTES, 'UTF-8') ?>">Seleccionar</button>
                                    </figure>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if (!$hayImagenesProductos): ?><p class="galeria-vacia">No hay productos activos con imagen disponible.</p><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="vista-previa-overlay" id="vista-previa-overlay" role="dialog" aria-modal="true" aria-labelledby="preview-titulo-modal">
        <div class="vista-previa-modal" id="vista-previa-modal">
            <div class="vista-previa-cabecera" id="vista-previa-cabecera">
                <strong id="preview-titulo-modal">Vista previa de inicio</strong>
                <button class="cerrar-preview" id="cerrar-preview" type="button" aria-label="Cerrar vista previa">x</button>
            </div>
            <div class="vista-previa-card">
                <div class="vista-previa-imagen">
                    <img id="preview-imagen" src="../IMG/Base.jpg" alt="Vista previa">
                </div>
                <div class="vista-previa-contenido">
                    <span class="promo-badge" id="preview-badge">Oferta</span>
                    <span class="vista-previa-categoria" id="preview-categoria">Categoría</span>
                    <h4 id="preview-titulo">Nombre del producto</h4>
                    <p id="preview-descripcion">Aún no hay descripción. Escribe algo para ver la vista previa.</p>
                    <div class="vista-previa-meta">
                        <span class="vista-previa-precio" id="preview-precio">$0</span>
                        <button class="vista-previa-boton" type="button">Ver oferta</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    function subirImagenProducto($archivo, $carpeta) {
        if (!isset($archivo) || $archivo['error'] !== UPLOAD_ERR_OK) {
            return '';
        }

        if (!is_dir($carpeta) && !mkdir($carpeta, 0775, true) && !is_dir($carpeta)) {
            return '';
        }

        $tipoImagen = !empty($archivo['tmp_name']) ? mime_content_type($archivo['tmp_name']) : '';
        if (!is_string($tipoImagen) || strpos($tipoImagen, 'image/') !== 0 || $archivo['size'] > 5 * 1024 * 1024) {
            return '';
        }

        $nombreArchivo = bin2hex(random_bytes(8)) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($archivo['name']));
        $rutaDestino = $carpeta . '/' . $nombreArchivo;

        if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            $rutaReal = realpath($rutaDestino);
            $raizProyecto = realpath(dirname(__DIR__));
            if ($rutaReal && $raizProyecto && strpos($rutaReal, $raizProyecto . DIRECTORY_SEPARATOR) === 0) {
                return str_replace(DIRECTORY_SEPARATOR, '/', substr($rutaReal, strlen($raizProyecto) + 1));
            }
        }

        return '';
    }

    function normalizarPrecioProducto($valor) {
        $precio = trim((string) $valor);
        if ($precio === '' || !preg_match('/^(?:[0-9]+|[0-9]{1,3}(?:[.,][0-9]{3})+)$/D', $precio)) {
            return false;
        }

        $precio = str_replace(['.', ','], '', $precio);
        $precioValidado = filter_var($precio, FILTER_VALIDATE_INT);
        return $precioValidado !== false && $precioValidado >= 0 ? $precioValidado : false;
    }

    if (isset($_POST['guardar'])) {
        $nombre = trim($_POST['nombre']);
        $precio = normalizarPrecioProducto($_POST['precio'] ?? '');
        $stock = (int) $_POST['stock'];
        $descripcion = trim($_POST['descripcion'] ?? '');
        $categoria = $_POST['categoria'] ?? '';
        $categoriasPermitidas = ['Piel', 'Cabello', 'Maquillaje', 'Accesorios'];
        $rutaImagen = trim($_POST['imagen_galeria_ruta'] ?? '');
        if ($rutaImagen === '') {
            $rutaImagen = subirImagenProducto($_FILES['imagen'] ?? null, __DIR__ . '/../IMG/productos');
            if (strpos($rutaImagen, 'IMG/IMG/productos/') === 0) {
                $rutaImagen = substr($rutaImagen, strlen('IMG/'));
            }
        }

        if ($precio !== false && $stock >= 0 && $nombre !== '' && in_array($categoria, $categoriasPermitidas, true)) {
            $verificarProducto = $conexion->prepare("SELECT id FROM productos WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?)) LIMIT 1");
            if ($verificarProducto) {
                $verificarProducto->bind_param('s', $nombre);
                $verificarProducto->execute();
                $productoExistente = $verificarProducto->get_result()->fetch_assoc();
                $verificarProducto->close();
            } else {
                $productoExistente = null;
            }

            if ($productoExistente) {
                echo '<div class="alerta-error">Ya existe un producto con ese nombre.</div>';
            } else {
                $rutaImagen = $rutaImagen !== '' ? $rutaImagen : null;
                $stmt = $conexion->prepare("INSERT INTO productos (nombre, precio, stock, descripcion, categoria, imagen, activo, administrador_id) VALUES (?, ?, ?, ?, ?, ?, 1, ?)");
                $stmt->bind_param('siisssi', $nombre, $precio, $stock, $descripcion, $categoria, $rutaImagen, $administradorId);
                $guardadoProducto = $stmt->execute();
                $stmt->close();
                echo $guardadoProducto
                    ? '<div class="alerta-exito">Producto agregado correctamente</div>'
                    : '<div class="alerta-error">No se pudo guardar el producto. Verifique los datos e inténtelo nuevamente.</div>';
            }
        } else {
            echo '<div class="alerta-error">Completa el nombre, precio, stock y selecciona una categoría válida.</div>';
        }
    }

    if (isset($_POST['eliminar_elemento'])) {
        $seleccionEliminar = trim($_POST['elemento_eliminar'] ?? '');
        if (preg_match('/^(producto|combo|promocion):([1-9][0-9]*)$/D', $seleccionEliminar, $coincidenciaEliminar)) {
            $tablasEliminar = [
                'producto' => 'productos',
                'combo' => 'combos',
                'promocion' => 'promociones',
            ];
            $tipoEliminar = $coincidenciaEliminar[1];
            $idEliminar = (int) $coincidenciaEliminar[2];
            $stmtEliminar = $conexion->prepare("DELETE FROM {$tablasEliminar[$tipoEliminar]} WHERE id = ?");
            if ($stmtEliminar) {
                $stmtEliminar->bind_param('i', $idEliminar);
                $ejecucionEliminar = $stmtEliminar->execute();
                $eliminado = $ejecucionEliminar && $stmtEliminar->affected_rows > 0;
                $stmtEliminar->close();
                echo $eliminado
                    ? '<div class="alerta-exito">Elemento eliminado correctamente.</div>'
                    : '<div class="alerta-error">' . ($ejecucionEliminar ? 'No se encontró el elemento seleccionado.' : 'No se pudo eliminar el elemento seleccionado.') . '</div>';
            } else {
                echo '<div class="alerta-error">No se pudo preparar la eliminación del elemento.</div>';
            }
        } else {
            echo '<div class="alerta-error">Selecciona un título válido para eliminar.</div>';
        }
    }

    if (isset($_POST['actualizar_producto'])) {
        $productoIdEdicion = (int) ($_POST['producto_id'] ?? 0);
        $nombreEdicion = trim($_POST['nombre_edicion'] ?? '');
        $precioEdicion = normalizarPrecioProducto($_POST['precio_edicion'] ?? '');
        $stockEdicion = filter_var($_POST['stock_edicion'] ?? null, FILTER_VALIDATE_INT);
        $fechaEdicion = trim($_POST['fecha_edicion'] ?? '');
        $fechaValida = DateTime::createFromFormat('Y-m-d\TH:i', $fechaEdicion);
        if ($productoIdEdicion > 0 && $nombreEdicion !== '' && $precioEdicion !== false && $precioEdicion >= 0 && $stockEdicion !== false && $stockEdicion >= 0 && $fechaValida) {
            $fechaEdicionSql = $fechaValida->format('Y-m-d H:i:s');
            $stmtEdicion = $conexion->prepare("UPDATE productos SET nombre = ?, precio = ?, stock = ?, fecha_creacion = ? WHERE id = ?");
            if ($stmtEdicion) {
                $stmtEdicion->bind_param('siisi', $nombreEdicion, $precioEdicion, $stockEdicion, $fechaEdicionSql, $productoIdEdicion);
                $actualizadoProducto = $stmtEdicion->execute();
                $stmtEdicion->close();
                echo $actualizadoProducto
                    ? '<div class="alerta-exito">Producto actualizado correctamente.</div>'
                    : '<div class="alerta-error">No se pudo actualizar. Verifica que el nombre no esté repetido.</div>';
            }
        } else {
            echo '<div class="alerta-error">Revisa el nombre, precio, stock y fecha del producto.</div>';
        }
    }

    if (isset($_POST['guardar_oferta'])) {
        $tipoOferta = $_POST['oferta_tipo'] ?? 'promocion';
        $titulo = trim($_POST['oferta_titulo'] ?? '');
        $descripcion = trim($_POST['oferta_descripcion'] ?? '');
        $categoria = $_POST['oferta_categoria'] ?? 'Piel';
        $productos = trim($_POST['oferta_productos'] ?? '');
        $precio = normalizarPrecioProducto($_POST['oferta_precio'] ?? '');
        $descuento = (float) ($_POST['oferta_descuento'] ?? 0);
        if (!in_array($tipoOferta, ['promocion', 'combo'], true)) {
            $tipoOferta = 'promocion';
        }
        $carpetaOferta = $tipoOferta === 'combo' ? __DIR__ . '/../IMG/combos' : __DIR__ . '/../IMG/promociones';
        $rutaImagen = trim($_POST['imagen_galeria_ruta'] ?? '');
        if ($rutaImagen === '') {
            $rutaImagen = subirImagenProducto($_FILES['oferta_imagen'] ?? null, $carpetaOferta);
        }

        $categoriasOfertaPermitidas = ['Piel', 'Cabello', 'Maquillaje', 'Accesorios'];
        $categoriaValida = $tipoOferta === 'combo' || in_array($categoria, $categoriasOfertaPermitidas, true);
        $rutaImagenReal = $rutaImagen !== '' ? realpath(dirname(__DIR__) . '/' . $rutaImagen) : false;
        $directorioImagenes = realpath(dirname(__DIR__) . '/IMG');
        $imagenValida = $rutaImagenReal && $directorioImagenes && strpos($rutaImagenReal, $directorioImagenes . DIRECTORY_SEPARATOR) === 0;

        if ($titulo !== '' && strlen($titulo) <= 150 && $descripcion !== '' && $categoriaValida && $precio !== false && $descuento >= 0 && $descuento <= 100 && $imagenValida) {
            if ($tipoOferta === 'combo') {
                $stmt = $conexion->prepare("INSERT INTO combos (nombre, descripcion, productos, precio, descuento, imagen, activo, administrador_id) VALUES (?, ?, ?, ?, ?, ?, 1, ?)");
                if ($stmt) $stmt->bind_param('sssidsi', $titulo, $descripcion, $productos, $precio, $descuento, $rutaImagen, $administradorId);
            } else {
                $stmt = $conexion->prepare("INSERT INTO promociones (titulo, descripcion, categoria, precio, descuento, imagen, activo, administrador_id) VALUES (?, ?, ?, ?, ?, ?, 1, ?)");
                if ($stmt) $stmt->bind_param('sssidsi', $titulo, $descripcion, $categoria, $precio, $descuento, $rutaImagen, $administradorId);
            }
            $guardado = $stmt && $stmt->execute();
            $ofertaId = $guardado ? (int) $conexion->insert_id : 0;
            $comboId = $guardado && $tipoOferta === 'combo' ? (int) $conexion->insert_id : 0;
            if ($stmt) $stmt->close();
            if ($guardado && $tipoOferta === 'combo' && $comboId > 0 && $productos !== '') {
                $buscarProductoCombo = $conexion->prepare("SELECT id FROM productos WHERE nombre = ? LIMIT 1");
                $insertarProductoCombo = $conexion->prepare("INSERT IGNORE INTO combo_productos (combo_id, producto_id, cantidad) VALUES (?, ?, 1)");
                if ($buscarProductoCombo && $insertarProductoCombo) {
                    foreach (array_unique(array_filter(array_map('trim', explode(',', $productos)))) as $nombreProductoCombo) {
                        $buscarProductoCombo->bind_param('s', $nombreProductoCombo);
                        $buscarProductoCombo->execute();
                        $resultadoProductoCombo = $buscarProductoCombo->get_result();
                        $productoCombo = $resultadoProductoCombo->fetch_assoc();
                        if ($productoCombo) {
                            $productoComboId = (int) $productoCombo['id'];
                            $insertarProductoCombo->bind_param('ii', $comboId, $productoComboId);
                            $insertarProductoCombo->execute();
                        }
                    }
                }
                if ($buscarProductoCombo) $buscarProductoCombo->close();
                if ($insertarProductoCombo) $insertarProductoCombo->close();
            }
            if ($guardado) {
                $precioNotificacion = round($precio * (100 - $descuento) / 100, 2);
                if (!notificarOfertaNuevosUsuarios($conexion, $tipoOferta, $ofertaId, $titulo, $descripcion, $precioNotificacion, $rutaImagen)) {
                    error_log('No se pudieron crear las notificaciones para la nueva oferta #' . $ofertaId . '.');
                }
                echo '<div class="alerta-exito">' . ($tipoOferta === 'combo' ? 'Combo' : 'Promoción') . ' agregado correctamente.</div>';
            } else {
                echo '<div class="alerta-error">No se pudo guardar la oferta. Verifique los datos e inténtelo nuevamente.</div>';
            }
        } else {
            echo '<div class="alerta-error">Revisa el título, descripción, categoría, precio en COP (ej. 140.000), descuento y la imagen. La imagen debe ser válida y pesar máximo 5 MB.</div>';
        }
    }

    ?>

    <div class="tabla-productos">
        <table>
            <thead>
                <tr>
                    <th>N.º</th>
                    <th>Nombre</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Fecha Creación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $resultado = mysqli_query($conexion, "SELECT * FROM productos ORDER BY id ASC");
                if (mysqli_num_rows($resultado) > 0) {
                    $numeroProducto = 0;
                    while ($row = mysqli_fetch_assoc($resultado)) {
                        $numeroProducto++;
                        $productoIdFila = (int) $row['id'];
                        $formularioFila = 'editar-producto-' . $productoIdFila;
                        $nombreFila = htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8');
                        $stockColor = (int) $row['stock'] === 0 ? 'color: #dc3545;' : '';
                        ?>
                        <tr>
                            <td><strong><?php echo $numeroProducto; ?></strong></td>
                            <td><span class="valor-visible"><?php echo $nombreFila; ?></span><input class="campo-edicion" name="nombre_edicion" form="<?php echo $formularioFila; ?>" type="text" value="<?php echo $nombreFila; ?>" required></td>
                            <td><span class="valor-visible">$<?php echo number_format((float) $row['precio'], 0, ',', '.'); ?></span><input class="campo-edicion" name="precio_edicion" form="<?php echo $formularioFila; ?>" type="text" inputmode="numeric" pattern="[0-9.,]+" value="<?php echo number_format((float) $row['precio'], 0, '', '.'); ?>" required></td>
                            <td style="<?php echo $stockColor; ?>"><span class="valor-visible"><strong><?php echo (int) $row['stock']; ?></strong> unidades</span><input class="campo-edicion" name="stock_edicion" form="<?php echo $formularioFila; ?>" type="number" min="0" value="<?php echo (int) $row['stock']; ?>" required></td>
                            <td><span class="valor-visible"><?php echo date('d/m/Y H:i', strtotime($row['fecha_creacion'])); ?></span><input class="campo-edicion" name="fecha_edicion" form="<?php echo $formularioFila; ?>" type="datetime-local" value="<?php echo date('Y-m-d\TH:i', strtotime($row['fecha_creacion'])); ?>" required></td>
                            <td>
                                <form id="<?php echo $formularioFila; ?>" method="POST">
                                    <input type="hidden" name="producto_id" value="<?php echo $productoIdFila; ?>">
                                    <input type="hidden" name="actualizar_producto" value="1">
                                    <button class="editar-fila" type="button" aria-label="Editar producto" title="Editar">✎</button>
                                    <button class="guardar-fila" type="submit">Guardar</button>
                                    <button class="cancelar-fila" type="button">Cancelar</button>
                                </form>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="6" style="text-align: center; padding: 30px; color: #999;">No hay productos registrados</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function formatearPrecio(valor) {
        const numero = Number(String(valor || 0).replace(/[.,]/g, ''));
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            maximumFractionDigits: 0
        }).format(numero);
    }

    function actualizarImagenPreview(input, img) {
        if (!input || !img) return;
        if (input.files && input.files[0]) {
            const lector = new FileReader();
            lector.onload = function (event) {
                img.src = event.target.result;
            };
            lector.readAsDataURL(input.files[0]);
        } else {
            img.src = '../IMG/Base.jpg';
        }
    }

    function actualizarImagenGaleria(ruta, texto, previewId) {
        const campoRuta = document.getElementById(previewId.replace('-preview', '-ruta'));
        const etiqueta = document.getElementById(previewId);
        if (campoRuta) campoRuta.value = ruta;
        if (etiqueta) etiqueta.textContent = texto;
    }

    function actualizarVistaPrevia(formId) {
        const previewTitulo = document.getElementById('preview-titulo');
        const previewDescripcion = document.getElementById('preview-descripcion');
        const previewCategoria = document.getElementById('preview-categoria');
        const previewBadge = document.getElementById('preview-badge');
        const previewPrecio = document.getElementById('preview-precio');
        const previewImagen = document.getElementById('preview-imagen');
        const previewModalTitulo = document.getElementById('preview-titulo-modal');

        const productoNombre = document.getElementById('producto-nombre')?.value.trim() || '';
        const productoDescripcion = document.getElementById('producto-descripcion')?.value.trim() || '';
        const productoCategoria = document.getElementById('producto-categoria')?.value || 'Categoría';
        const productoPrecio = document.getElementById('producto-precio')?.value || '';

        const ofertaTipo = document.getElementById('oferta-tipo')?.value || 'promocion';
        const ofertaTitulo = document.getElementById('oferta-titulo')?.value.trim() || '';
        const ofertaDescripcion = document.getElementById('oferta-descripcion')?.value.trim() || '';
        const ofertaCategoria = document.getElementById('oferta-categoria')?.value || 'Categoría';
        const ofertaProductos = document.getElementById('oferta-productos')?.value.trim() || '';
        const ofertaPrecio = document.getElementById('oferta-precio')?.value || '';

        if (formId === 'form-producto') {
            previewModalTitulo.textContent = 'Vista previa del producto';
            previewBadge.textContent = 'Producto';
            previewTitulo.textContent = productoNombre;
            previewDescripcion.textContent = productoDescripcion || 'Aún no hay descripción. Escribe algo para ver la vista previa.';
            previewCategoria.textContent = productoCategoria;
            previewPrecio.textContent = formatearPrecio(productoPrecio);
            const rutaProducto = document.getElementById('producto-imagen-ruta')?.value || '';
            previewImagen.src = rutaProducto ? '../' + rutaProducto : '../IMG/Base.jpg';
            return;
        }

        if (formId === 'form-promocion-combo') {
            previewModalTitulo.textContent = 'Vista previa de inicio';
            previewBadge.textContent = ofertaTipo === 'combo' ? 'Combo' : 'Oferta';
            previewTitulo.textContent = ofertaTitulo;
            previewDescripcion.textContent = ofertaDescripcion || (ofertaProductos ? 'Incluye: ' + ofertaProductos : 'Aún no hay descripción. Escribe algo para ver la vista previa.');
            previewCategoria.textContent = ofertaTipo === 'combo' ? 'Combo' : ofertaCategoria;
            previewPrecio.textContent = formatearPrecio(ofertaPrecio);
            const rutaOferta = document.getElementById('oferta-imagen-ruta')?.value || '';
            previewImagen.src = rutaOferta ? '../' + rutaOferta : '../IMG/Base.jpg';
            return;
        }

        previewModalTitulo.textContent = 'Vista previa de inicio';
        previewTitulo.textContent = 'Nombre del producto';
        previewBadge.textContent = 'Oferta';
        previewDescripcion.textContent = 'Aún no hay descripción. Escribe algo para ver la vista previa.';
        previewCategoria.textContent = 'Categoría';
        previewPrecio.textContent = '$0';

        actualizarImagenPreview(document.getElementById('producto-imagen'), previewImagen);
    }

    const overlayPreview = document.getElementById('vista-previa-overlay');
    const modalPreview = document.getElementById('vista-previa-modal');
    const cerrarPreview = document.getElementById('cerrar-preview');
    const cabeceraPreview = document.getElementById('vista-previa-cabecera');
    const overlayGaleria = document.getElementById('galeria-overlay');
    const cerrarGaleria = document.getElementById('cerrar-galeria');
    const overlayGaleriaProductos = document.getElementById('galeria-productos-overlay');
    const cerrarGaleriaProductos = document.getElementById('cerrar-galeria-productos');
    const botonBuscarImagenProducto = document.getElementById('buscar-imagen-producto');
    const inputImagenProducto = document.getElementById('producto-imagen');
    const botonBuscarImagenOferta = document.getElementById('buscar-imagen-oferta');
    const inputImagenOferta = document.getElementById('oferta-imagen');
    let destinoGaleria = null;

    botonBuscarImagenProducto?.addEventListener('click', () => inputImagenProducto?.click());
    botonBuscarImagenOferta?.addEventListener('click', () => inputImagenOferta?.click());

    function abrirPreview(formId) {
        overlayPreview.classList.add('visible');
        document.body.classList.add('preview-abierto');
        actualizarVistaPrevia(formId);
    }

    document.querySelectorAll('.abrir-preview').forEach((boton) => {
        boton.addEventListener('click', () => abrirPreview(boton.dataset.previewForm));
    });

    function cerrarModalPreview() {
        overlayPreview.classList.remove('visible');
        document.body.classList.remove('preview-abierto');
    }

    cerrarPreview.addEventListener('click', cerrarModalPreview);
    overlayPreview.addEventListener('click', (event) => {
        if (event.target === overlayPreview) cerrarModalPreview();
    });

    const parametrosPagina = new URLSearchParams(window.location.search);
    if (parametrosPagina.get('galeria_abierta') === '1') {
        overlayGaleria.classList.add('visible');
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    document.querySelectorAll('.selector-galeria').forEach((boton) => {
        boton.addEventListener('click', () => {
            if (!boton.dataset.galeriaTarget) return;
            destinoGaleria = {
                campo: boton.dataset.galeriaTarget,
                preview: boton.dataset.galeriaPreview
            };
            const selectorProductos = boton.dataset.galeriaSource === 'productos';
            (selectorProductos ? overlayGaleriaProductos : overlayGaleria).classList.add('visible');
        });
    });

    document.querySelectorAll('.usar-imagen-galeria, .usar-imagen-producto').forEach((boton) => {
        boton.addEventListener('click', () => {
            if (!destinoGaleria) return;
            const campoRuta = document.getElementById(destinoGaleria.campo);
            const etiqueta = document.getElementById(destinoGaleria.preview);
            const imagenPreview = document.getElementById(destinoGaleria.preview.replace('-preview', '-elegida'));
            if (campoRuta) campoRuta.value = boton.dataset.ruta;
            if (etiqueta) etiqueta.textContent = boton.dataset.nombre;
            if (imagenPreview) {
                imagenPreview.src = '../' + boton.dataset.ruta;
                imagenPreview.style.display = 'block';
            }
            const botonFormulario = document.querySelector('[data-galeria-target="' + destinoGaleria.campo + '"]');
            if (botonFormulario) botonFormulario.textContent = 'Seleccionar imagen';
            overlayGaleria.classList.remove('visible');
            overlayGaleriaProductos.classList.remove('visible');
        });
    });

    document.querySelectorAll('.formulario-producto input[type="file"][accept="image/*"]').forEach((input) => {
        if (input.id === 'imagen-galeria-subir') return;
        input.addEventListener('change', () => {
            const formulario = input.closest('form');
            const imagenPreview = formulario?.querySelector('.imagen-elegida-preview');
            const campoRuta = formulario?.querySelector('input[name="imagen_galeria_ruta"]');
            const etiqueta = formulario?.querySelector('.ruta-imagen-seleccionada');
            if (input.files?.[0] && imagenPreview) {
                imagenPreview.src = URL.createObjectURL(input.files[0]);
                imagenPreview.style.display = 'block';
                if (campoRuta) campoRuta.value = '';
                if (etiqueta) etiqueta.textContent = input.files[0].name;
            }
        });
    });

    document.querySelectorAll('.editar-fila').forEach((boton) => {
        boton.addEventListener('click', () => boton.closest('tr')?.classList.add('editando'));
    });

    document.querySelectorAll('.cancelar-fila').forEach((boton) => {
        boton.addEventListener('click', () => {
            const fila = boton.closest('tr');
            if (!fila) return;
            fila.querySelectorAll('.campo-edicion').forEach((campo) => campo.value = campo.defaultValue);
            fila.classList.remove('editando');
        });
    });

    document.querySelectorAll('[data-confirm-delete]').forEach((formulario) => {
        formulario.addEventListener('submit', (event) => {
            const tituloSeleccionado = formulario.querySelector('[name="elemento_eliminar"]')?.selectedOptions[0]?.textContent || '';
            if (!window.confirm('¿Eliminar "' + tituloSeleccionado + '"? Esta acción no se puede deshacer.')) {
                event.preventDefault();
            }
        });
    });

    const tipoOfertaSelect = document.getElementById('oferta-tipo');
    const categoriaOfertaSelect = document.getElementById('oferta-categoria');
    const productosOfertaInput = document.getElementById('oferta-productos');
    function actualizarCamposOferta() {
        const esCombo = tipoOfertaSelect.value === 'combo';
        categoriaOfertaSelect.disabled = esCombo;
        categoriaOfertaSelect.required = !esCombo;
        categoriaOfertaSelect.hidden = esCombo;
        productosOfertaInput.hidden = !esCombo;
    }
    tipoOfertaSelect.addEventListener('change', actualizarCamposOferta);
    actualizarCamposOferta();

    document.getElementById('imagen-galeria-subir').addEventListener('change', (event) => {
        const nombreImagen = document.getElementById('nombre-imagen-galeria');
        nombreImagen.textContent = event.target.files[0]?.name || 'Seleccionar imagen nueva';
    });

    cerrarGaleria.addEventListener('click', () => {
        overlayGaleria.classList.remove('visible');
    });

    cerrarGaleriaProductos.addEventListener('click', () => {
        overlayGaleriaProductos.classList.remove('visible');
    });

    overlayGaleria.addEventListener('click', (event) => {
        if (event.target === overlayGaleria) overlayGaleria.classList.remove('visible');
    });

    overlayGaleriaProductos.addEventListener('click', (event) => {
        if (event.target === overlayGaleriaProductos) overlayGaleriaProductos.classList.remove('visible');
    });

    let arrastrando = false;
    let desplazamientoX = 0;
    let desplazamientoY = 0;

    cabeceraPreview.addEventListener('pointerdown', (event) => {
        arrastrando = true;
        const rect = modalPreview.getBoundingClientRect();
        modalPreview.style.transform = 'none';
        modalPreview.style.left = rect.left + 'px';
        modalPreview.style.top = rect.top + 'px';
        desplazamientoX = event.clientX - rect.left;
        desplazamientoY = event.clientY - rect.top;
        cabeceraPreview.setPointerCapture(event.pointerId);
    });

    cabeceraPreview.addEventListener('pointermove', (event) => {
        if (!arrastrando) return;
        modalPreview.style.left = Math.max(8, event.clientX - desplazamientoX) + 'px';
        modalPreview.style.top = Math.max(8, event.clientY - desplazamientoY) + 'px';
    });

    cabeceraPreview.addEventListener('pointerup', () => {
        arrastrando = false;
    });

</script>