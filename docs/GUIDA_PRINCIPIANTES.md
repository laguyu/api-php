# Guía para probar la API sin saber PHP

Esta guía está pensada para alguien que quiere probar la API desde cero, sin necesidad de conocer PHP a profundidad.

## 1. Requisitos

Necesitas lo siguiente:

- Laragon instalado con Apache y MySQL o PostgreSQL activados
- PHP 8.1 o superior
- Una herramienta para probar APIs, como Postman o simplemente la terminal de Windows

Si no tienes Laragon, también puedes ejecutar la API localmente con PHP desde la terminal.

---

## 2. Abrir el proyecto

1. Coloca la carpeta del proyecto dentro de la carpeta `www` de Laragon.
2. Abre la terminal en la raíz del proyecto.
3. Ejecuta este comando:

```bash
php -S 127.0.0.1:8000 -t public
```

Si todo está bien, verás un mensaje similar a:

```text
PHP 8.x Development Server (http://127.0.0.1:8000) started
```

Ahora la API ya está lista para probarse.

---

## 3. Probar la API

### Opción A: desde la terminal

#### Listar productos con API key

```bash
curl.exe -i -H "X-Api-Key: TU_API_KEY_DE_CONFIG" http://127.0.0.1:8000/api/products
```

#### Login para obtener JWT

```bash
curl.exe -i -X POST http://127.0.0.1:8000/api/auth/login -H "Content-Type: application/json" -d "{\"email\":\"admin@example.com\",\"password\":\"Admin123!\"}"
```

La respuesta incluye `access_token` y `refresh_token`.

Importante: el `refresh_token` es de un solo uso. Cada vez que llamas a `/api/auth/refresh`, el token anterior se invalida. Si llamas a `/api/auth/logout`, también se revoca y ya no puede usarse.

#### Usar JWT para listar productos

```bash
curl.exe -i -H "Authorization: Bearer TU_ACCESS_TOKEN" http://127.0.0.1:8000/api/products
```

#### Renovar JWT con refresh token

```bash
curl.exe -i -X POST http://127.0.0.1:8000/api/auth/refresh -H "Content-Type: application/json" -d "{\"refresh_token\":\"TU_REFRESH_TOKEN\"}"
```

#### Logout (revocar refresh token)

```bash
curl.exe -i -X POST http://127.0.0.1:8000/api/auth/logout -H "Content-Type: application/json" -d "{\"refresh_token\":\"TU_REFRESH_TOKEN\"}"
```

#### Crear un producto

```bash
curl.exe -i -X POST http://127.0.0.1:8000/api/products -H "Content-Type: application/json" -H "X-Api-Key: TU_API_KEY_DE_CONFIG" -d "{\"name\":\"Teclado\",\"description\":\"Teclado mecánico\",\"price\":49.99}"
```

#### Actualizar un producto

```bash
curl.exe -i -X PUT http://127.0.0.1:8000/api/products/1 -H "Content-Type: application/json" -H "X-Api-Key: TU_API_KEY_DE_CONFIG" -d "{\"price\":59.99}"
```

#### Eliminar un producto

```bash
curl.exe -i -X DELETE http://127.0.0.1:8000/api/products/1 -H "X-Api-Key: TU_API_KEY_DE_CONFIG"
```

> Nota: en PowerShell debes usar `curl.exe`, no solo `curl`.

### Opción B: desde Postman

1. Abre Postman.
2. Crea una nueva petición.
3. En la URL pega:
   - `http://127.0.0.1:8000/api/products`
4. Añade este header:
    - `X-Api-Key: TU_API_KEY_DE_CONFIG`
5. Si vas a crear o actualizar un producto, usa `Content-Type: application/json` y el body en formato JSON.

---

## 4. Qué esperar como respuesta

Si la petición funciona, la API responderá con JSON.

Ejemplo de respuesta al listar productos:

```json
[
  {
    "id": 1,
    "name": "Laptop",
    "description": "Laptop de alta gama",
    "price": 1499.99,
    "created_at": "2026-06-30 23:52:27"
  }
]
```

Si algo falla, la API responderá con un mensaje de error como:

```json
{
  "error": "Bad Request",
  "message": "The product name is required."
}
```

---

## 5. Conectar la API con MySQL en Laragon

### Paso 1: iniciar MySQL

1. Abre Laragon.
2. Asegúrate de que el servicio de MySQL esté encendido.
3. Abre `http://localhost/phpmyadmin`.

### Paso 2: crear la base de datos

En phpMyAdmin:

1. Haz clic en "Nueva".
2. Crea una base de datos llamada:

```text
api_solid_db
```

3. Haz clic en "Crear".

### Paso 3: ajustar la configuración

Abre el archivo `config.php` y cambia lo siguiente:

```php
return [
    'active_driver' => 'mysql',
    'auth' => [
        'api_key' => 'TU_API_KEY_DE_CONFIG',
        'jwt_secret' => 'TU_JWT_SECRET'
    ],
    'database' => [
        'mysql' => [
            'host' => '127.0.0.1',
            'port' => '3306',
            'dbname' => 'api_solid_db',
            'username' => 'root',
            'password' => ''
        ]
    ]
];
```

### Paso 4: reiniciar la API

Detén el servidor y vuelve a iniciarlo:

```bash
php -S 127.0.0.1:8000 -t public
```

La tabla `products` se creará automáticamente al acceder a la API.

> Si recibes un error de conexión, revisa que MySQL esté realmente encendido en Laragon y que el usuario/contraseña sean correctos.

---

## 6. Conectar la API con PostgreSQL

### Paso 1: preparar PostgreSQL

Si tienes PostgreSQL instalado, asegúrate de que el servicio esté activo.

### Paso 2: crear la base de datos

Puedes crear la base de datos desde pgAdmin o con la terminal:

```sql
CREATE DATABASE api_solid_db;
```

### Paso 3: ajustar la configuración

En `config.php` cambia:

```php
return [
    'active_driver' => 'pgsql',
    'auth' => [
        'api_key' => 'TU_API_KEY_DE_CONFIG',
        'jwt_secret' => 'TU_JWT_SECRET'
    ],
    'database' => [
        'pgsql' => [
            'host' => '127.0.0.1',
            'port' => '5432',
            'dbname' => 'api_solid_db',
            'username' => 'postgres',
            'password' => 'postgres_password'
        ]
    ]
];
```

### Paso 4: reiniciar la API

```bash
php -S 127.0.0.1:8000 -t public
```

La tabla `products` también se creará automáticamente.

---

## 7. Problemas comunes

### Error 401 Unauthorized

Significa que faltó la cabecera `X-Api-Key` o que el JWT es inválido/expirado.

Solución:

```bash
-H "X-Api-Key: TU_API_KEY_DE_CONFIG"
```

### Credenciales de login para pruebas

La API crea estos usuarios automáticamente en la tabla `users`:

- `admin@example.com` / `Admin123!`
- `editor@example.com` / `Editor123!`
- `viewer@example.com` / `Viewer123!`

### Error de conexión a la base de datos

Revisa:

- Si el servicio de MySQL/PostgreSQL está encendido
- Si el nombre de la base de datos es correcto
- Si el usuario y contraseña coinciden con los de tu instalación

### Error 429 Too Many Requests en login

Significa que se excedieron los intentos fallidos de login por IP.

Debes esperar el tiempo de bloqueo o ajustar la configuración de rate limit.

Variables disponibles en `config.php`:

- `LOGIN_MAX_ATTEMPTS`
- `LOGIN_WINDOW_SECONDS`
- `LOGIN_BLOCK_SECONDS`

### Error 404 en la ruta

Asegúrate de usar la URL correcta:

```text
http://127.0.0.1:8000/api/products
```

---

## 8. Resumen rápido

Si quieres probarla rápido, haz esto:

1. Ejecuta:

```bash
php -S 127.0.0.1:8000 -t public
```

2. Usa esta llamada:

```bash
curl.exe -i -H "X-Api-Key: TU_API_KEY_DE_CONFIG" http://127.0.0.1:8000/api/products

# o usando JWT
curl.exe -i -H "Authorization: Bearer TU_ACCESS_TOKEN" http://127.0.0.1:8000/api/products
```

3. Si todo está bien, verás la lista de productos o un JSON vacío si todavía no hay registros.

---

## 9. Variables de entorno (recomendado)

Para no dejar secretos en el código, puedes definir variables de entorno en Windows antes de levantar la API.

Ejemplo en PowerShell:

```powershell
$env:API_KEY = "tu_api_key_super_segura"
$env:JWT_SECRET = "tu_jwt_secret_super_seguro"
$env:LOGIN_MAX_ATTEMPTS = "5"
$env:LOGIN_WINDOW_SECONDS = "900"
$env:LOGIN_BLOCK_SECONDS = "900"
php -S 127.0.0.1:8000 -t public
```

Si trabajas con MySQL o PostgreSQL también puedes definir:

```powershell
$env:MYSQL_HOST = "127.0.0.1"
$env:MYSQL_PORT = "3306"
$env:MYSQL_DBNAME = "api_solid_db"
$env:MYSQL_USER = "root"
$env:MYSQL_PASSWORD = ""
```
