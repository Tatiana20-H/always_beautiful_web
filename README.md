# Always Beautiful Web Project

## Arranque con Docker y VirtualBox

El proyecto usa dos contenedores: Apache/PHP y MySQL. Docker publica Apache en los puertos `80` y `8080` de la máquina virtual. Para abrirlo desde el navegador de Windows mediante VirtualBox, crea una regla NAT:

- Protocolo: `TCP`
- Puerto del anfitrión: `80`
- IP invitado: dejar vacía
- Puerto invitado: `80`

Después de iniciar la máquina virtual, ejecuta desde esta carpeta:

```bash
sudo groupadd docker 2>/dev/null || true
sudo usermod -aG docker "$USER"
```

Cierra la sesión de la VM y vuelve a entrar una vez para aplicar el permiso. Si no quieres cerrar sesión, ejecuta temporalmente los comandos con `sudo`. Luego inicia el proyecto con:

```bash
docker compose up -d
docker compose ps
```

La conexión PHP usa `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD` y `DB_NAME`. Docker ya proporciona estos valores mediante `compose.yaml`. Si ejecutas el proyecto directamente con Apache/XAMPP en Windows, configura esas variables con los datos de tu MySQL local; especialmente `DB_PASSWORD`, porque no debe quedar escrita en el código.

En una base nueva, ejecuta `Base de datos de alwaysbeautifulweb.sql` (estructura), `Usuarios.sql` (cuentas) y `Productos.sql` (catálogo), en ese orden. En MySQL Workbench abre cada archivo, selecciona todo con `Ctrl+A` y ejecuta con el botón del rayo. No ejecutes solo un fragmento seleccionado. Los scripts de inicialización de Docker solo se ejecutan cuando se crea el volumen `db_data`.

El archivo principal contiene sentencias SQL estándar, sin procedimientos ni `DELIMITER`, por lo que sirve tanto para Workbench como para la inicialización PHP del sitio. Las tablas no fijan `CHARSET` ni `COLLATION`: heredan los valores predeterminados del servidor MySQL/MariaDB. PHP mantiene UTF-8 en la conexión para guardar correctamente nombres y mensajes con tildes. Usa `CREATE TABLE IF NOT EXISTS`: en una base existente conserva los datos, pero no cambia automáticamente tablas antiguas que ya existen.

Para actualizar una base creada con el esquema histórico del proyecto, haz primero una copia de seguridad. Ejecuta una sola vez `migracion_relaciones_compras.sql` y después importa `Base de datos de alwaysbeautifulweb.sql` para crear las tablas nuevas restantes. La migración antigua requiere que las tablas `carrito`, `pedidos` y `detalles_pedido` tengan sus nombres de restricciones originales; si Workbench informa que no encuentra `detalles_pedido_ibfk_2`, consulta `SHOW CREATE TABLE detalles_pedido` y usa el nombre real de esa FK.

El modelo y las cardinalidades están documentados en `MODELO_ENTIDAD_RELACION.md`. Verifica las relaciones con:

```sql
SHOW CREATE TABLE carrito;
SHOW CREATE TABLE reseñas_productos;
SHOW CREATE TABLE pedidos;
SHOW CREATE TABLE detalles_pedido;
SHOW CREATE TABLE combo_productos;
```

Antes de migrar otra instalación, comprueba que el catálogo no tenga nombres repetidos y que el carrito no tenga varias líneas para el mismo producto y usuario; esas duplicidades impedirían crear las claves `UNIQUE`:

```sql
SELECT nombre, COUNT(*) FROM productos GROUP BY nombre HAVING COUNT(*) > 1;
SELECT usuario_id, producto_id, COUNT(*) FROM carrito GROUP BY usuario_id, producto_id HAVING COUNT(*) > 1;
```

Docker ejecuta el esquema automáticamente al crear por primera vez el volumen `db_data`. El volumen activo de este workspace ya contiene el esquema actualizado; no es necesario borrar ni reinicializarlo.

Abre en Windows `http://localhost/` o `http://localhost:8080/`. Para Live Share, comparte el puerto `80` desde VS Code y tu compañero debe abrir la URL que Live Share le proporcione. La base de datos se conserva en el volumen `db_data` y Compose espera a que MySQL esté listo antes de iniciar la web.

El mismo formulario de inicio de sesión sirve para ambos roles:

- Usuario: correo y contraseña de la cuenta registrada.
- Administrador: `Tatianaadmin@gmail.com` con contraseña `123456`.

Al iniciar sesión con el rol `admin`, la aplicación redirige automáticamente a `Administrador/index.php`.

## Qué se mantiene
- Archivos PHP y recursos web: la mayor parte del sitio se encuentra en la raíz y en `Administrador/`.
- Imágenes en `IMG/`.
- Archivos de configuración y datos útiles como `WEB-INF/web.xml`, scripts SQL y archivos PHP.
- Código Java fuente usado por la aplicación web basada en servlets en `Administrador/servlet`, `dao/`, `conexion/`, `modelo/`.
- `api-auth/` es un proyecto separado de backend REST y se mantiene también.

## Qué está generado y se puede limpiar
Los siguientes elementos son artefactos generados que no deben mantenerse en el control de versiones y pueden borrarse:
- `out/`
- `target/`
- `always-beautiful/`
- `api-auth/target/`
- archivos `.class` dentro de `dao/`, `conexion/`, `modelo/`

## Recomendaciones para ordenar el proyecto
1. Ejecuta `cleanup-generated-web-files.bat` para borrar los artefactos generados.
2. Mantén los archivos PHP en la raíz y la carpeta `Administrador/` como el sitio web funcional.
3. Deja `api-auth/` como proyecto backend separado.
4. Si quieres estructurar el proyecto, considera crear carpetas como:
   - `public/` para archivos HTML/PHP públicos
   - `assets/` para `IMG/`, `styles.css` y otros recursos estáticos
   - `backend/` para el código Java si sigues usando los servlets en `Administrador/servlet`

## Cómo usar la limpieza
- En Windows, abre un terminal en la carpeta del proyecto y ejecuta:
  ```bat
  cleanup-generated-web-files.bat
  ```
- Esto eliminará los archivos generados sin tocar los archivos fuente PHP o Java.
