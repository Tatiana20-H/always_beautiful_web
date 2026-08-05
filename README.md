# Always Beautiful Web Project

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
