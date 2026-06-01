# Always Beautiful – API de Autenticación
### GA7-220501096-AA5-EV01 · Diseño y Desarrollo de Servicios Web

API REST desarrollada con **Spring Boot 3** que implementa el registro de usuarios e inicio de sesión con autenticación segura mediante **BCrypt**.

---

## Endpoints del Servicio

| Método | URL                   | Descripción                        | Público |
|--------|-----------------------|------------------------------------|---------|
| POST   | `/api/auth/registro`  | Registrar un nuevo usuario         | ✅ Sí   |
| POST   | `/api/auth/login`     | Iniciar sesión                     | ✅ Sí   |
| GET    | `/api/auth/salud`     | Verificar que el servicio está activo | ✅ Sí |

---

## Ejemplos de Uso

### Registro
```http
POST http://localhost:8080/api/auth/registro
Content-Type: application/json

{
  "nombre": "Ana García",
  "correo": "ana@mail.com",
  "contrasena": "miClave123"
}
```
**Respuesta exitosa (201 Created):**
```json
{
  "exito": true,
  "mensaje": "Registro exitoso. ¡Bienvenido/a a Always Beautiful!",
  "usuario": {
    "id": 1,
    "nombre": "Ana García",
    "correo": "ana@mail.com",
    "rol": "CLIENTE"
  }
}
```

### Inicio de Sesión
```http
POST http://localhost:8080/api/auth/login
Content-Type: application/json

{
  "correo": "ana@mail.com",
  "contrasena": "miClave123"
}
```
**Respuesta exitosa (200 OK):**
```json
{
  "exito": true,
  "mensaje": "Autenticación satisfactoria. ¡Bienvenido/a, Ana García!",
  "usuario": {
    "id": 1,
    "nombre": "Ana García",
    "correo": "ana@mail.com",
    "rol": "CLIENTE"
  }
}
```
**Respuesta con error (401 Unauthorized):**
```json
{
  "exito": false,
  "mensaje": "Error en la autenticación: credenciales incorrectas"
}
```

---

## Prerrequisitos

- Java 17+
- Maven 3.8+
- MySQL 8.0 con la base de datos `AlwaysBeautifulDB` activa
- El servidor PHP del proyecto already-beautiful debe estar configurado

---

## Configuración

Editar `src/main/resources/application.properties` con las credenciales de la BD:

```properties
spring.datasource.url=jdbc:mysql://localhost:3306/AlwaysBeautifulDB
spring.datasource.username=root
spring.datasource.password=TU_PASSWORD
```

---

## Cómo Ejecutar

```bash
# 1. Clonar el repositorio
git clone https://github.com/tu-usuario/always-beautiful-auth.git
cd always-beautiful-auth

# 2. Compilar y ejecutar
mvn spring-boot:run

# 3. Ejecutar pruebas unitarias
mvn test
```

---

## Versionamiento con Git

Este proyecto usa **Git** para control de versiones. Comandos esenciales:

```bash
# Inicializar repositorio (solo la primera vez)
git init

# Ver estado de los archivos
git status

# Agregar todos los archivos al área de staging
git add .

# Crear commit con mensaje descriptivo
git commit -m "feat: agregar API de autenticación Always Beautiful"

# Conectar con repositorio remoto en GitHub
git remote add origin https://github.com/tu-usuario/always-beautiful-auth.git

# Subir al repositorio remoto
git push -u origin main
```

### Convención de mensajes de commit:
| Prefijo   | Uso                                           |
|-----------|-----------------------------------------------|
| `feat:`   | Nueva funcionalidad                           |
| `fix:`    | Corrección de error                           |
| `docs:`   | Cambios en documentación                      |
| `test:`   | Agregar o corregir pruebas                    |
| `refactor:` | Refactorización sin cambio de funcionalidad |

---

## Estructura del Proyecto

```
always-beautiful-auth/
├── src/main/java/com/beauty/alwaysbeautiful/
│   ├── AlwaysBeautifulApplication.java   ← Punto de entrada
│   ├── controller/
│   │   ├── AuthController.java           ← Endpoints REST
│   │   └── GlobalExceptionHandler.java   ← Manejo de errores
│   ├── service/
│   │   └── AuthService.java              ← Lógica de negocio
│   ├── repository/
│   │   └── UsuarioRepository.java        ← Acceso a datos (JPA)
│   ├── model/
│   │   └── Usuario.java                  ← Entidad de base de datos
│   ├── dto/
│   │   ├── RegistroRequest.java          ← DTO entrada registro
│   │   ├── LoginRequest.java             ← DTO entrada login
│   │   └── AuthResponse.java             ← DTO respuesta unificada
│   └── security/
│       └── SecurityConfig.java           ← Configuración seguridad + BCrypt
├── src/main/resources/
│   ├── application.properties            ← Configuración BD y servidor
│   └── schema.sql                        ← Script creación tabla usuarios
├── src/test/java/.../
│   └── AuthServiceTest.java              ← Pruebas unitarias
└── pom.xml                               ← Dependencias Maven
```

---

## Seguridad Implementada

- **BCrypt (factor 12):** las contraseñas nunca se almacenan en texto plano
- **Respuestas genéricas:** el mensaje de error no indica si el correo o la contraseña son incorrectos (previene enumeración de usuarios)
- **API Stateless:** sin sesiones HTTP, adecuado para REST
- **Bean Validation:** validación automática de campos de entrada
- **CORS habilitado:** el front-end PHP puede consumir la API sin restricciones de origen
