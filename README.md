# Documentación de la API de Productos y Guía de Base de Datos

Esta guía detalla el funcionamiento de la API, cómo probar sus endpoints utilizando Postman o Swagger, y cómo aprovechar los principios SOLID de la arquitectura para cambiar de base de datos (SQLite, MySQL, PostgreSQL) de forma sencilla.

## Resumen para reclutadores

Este proyecto es una API REST construida en PHP nativo aplicando arquitectura en capas y principios SOLID.

Objetivo del proyecto:

- Demostrar diseño backend mantenible sin framework pesado.
- Aplicar buenas prácticas de seguridad y autenticación.
- Mostrar despliegue cloud-ready (Vercel) con base de datos administrada en Aiven MySQL.

Características técnicas destacadas:

- CRUD de productos con validación por DTOs.
- Autenticación con JWT y refresh token.
- Revocación de sesión (logout) y control de sesiones activas.
- Rate limiting para login por IP.
- Documentación OpenAPI y Swagger UI integradas.
- Configuración por variables de entorno para local y producción.

## Principios SOLID aplicados

- SRP (Single Responsibility): controladores, servicios, DTOs, repositorios y conexiones están separados por responsabilidad.
- OCP (Open/Closed): puedes agregar nuevos repositorios o drivers sin reescribir la lógica principal.
- LSP (Liskov Substitution): cualquier implementación de repositorio o conexión que respete la interfaz puede sustituirse.
- ISP (Interface Segregation): contratos separados para productos, usuarios y sesiones (`ProductRepositoryInterface`, `UserRepositoryInterface`, `AuthSessionRepositoryInterface`).
- DIP (Dependency Inversion): la aplicación depende de abstracciones (`DatabaseConnectionInterface`, interfaces de repositorio), no de clases concretas.

## Stack y herramientas usadas

- Lenguaje: PHP 8.3
- Persistencia: PDO
- Bases de datos: MySQL (local/Laragon), SQLite (fallback), MySQL administrado en Aiven (producción)
- Arquitectura: capas `Core`, `Application`, `Domain`, `Infrastructure`
- Seguridad: API Key + JWT (Bearer), refresh token, rate limiting
- Documentación API: OpenAPI 3 + Swagger UI
- Deploy serverless: Vercel (`vercel-php` runtime)
- Entorno y configuración: `.env`, `config.php`

## Arquitectura y seguridad aplicada

La API fue reforzada con una estructura más limpia y segura para portafolio:

- Separación de responsabilidades entre controlador, servicio, DTOs y repositorio.
- Validación y sanitización de entradas antes de crear o actualizar productos.
- Middleware de autenticación con API key o JWT para proteger los endpoints.
- Repositorio con consultas preparadas y mapeo independiente de la capa de infraestructura.
- Flujo real de login y refresh token con usuarios en base de datos.
- Rotación e invalidación de refresh token en cada uso.
- Rate limit por IP para `POST /api/auth/login`.
- Soporte para secretos por variables de entorno (`API_KEY`, `JWT_SECRET`, etc.).

### Autenticación

Puedes usar dos formas de autenticación:

1. API key (rápida para pruebas):
```bash
curl.exe -H "X-Api-Key: <tu_api_key_de_config.php>" http://127.0.0.1:8000/api/products
```

2. JWT con login:
```bash
curl.exe -X POST http://127.0.0.1:8000/api/auth/login -H "Content-Type: application/json" -d "{\"email\":\"admin@example.com\",\"password\":\"Admin123!\"}"
```

Usuarios de prueba creados automáticamente:

- `admin@example.com` / `Admin123!`
- `editor@example.com` / `Editor123!`
- `viewer@example.com` / `Viewer123!`

El endpoint de login devuelve `access_token` y `refresh_token`.

Al refrescar token (`POST /api/auth/refresh`), el refresh token anterior queda invalidado.

El endpoint `POST /api/auth/logout` revoca el refresh token enviado y cierra la sesión activa.

Si una IP excede intentos fallidos de login, la API responde `429 Too Many Requests`.

### Variables de entorno recomendadas

Puedes definir variables de entorno para no exponer secretos en código:

- `API_KEY`
- `JWT_SECRET`
- `LOGIN_MAX_ATTEMPTS`
- `LOGIN_WINDOW_SECONDS`
- `LOGIN_BLOCK_SECONDS`
- `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_DBNAME`, `MYSQL_USER`, `MYSQL_PASSWORD`
- `MYSQL_AUTO_CREATE_DATABASE`, `MYSQL_INIT_SCHEMA`, `MYSQL_SEED_DEFAULT_USERS`
- `MYSQL_SSL_MODE`, `MYSQL_SSL_CA`, `MYSQL_SSL_VERIFY_SERVER_CERT`
- `ACTIVE_DRIVER` (`mysql`, `sqlite`, `pgsql`)
- `PGSQL_HOST`, `PGSQL_PORT`, `PGSQL_DBNAME`, `PGSQL_USER`, `PGSQL_PASSWORD`

### Usar MySQL en local

1. Crea tu archivo local `.env` a partir de `.env.example` y ajusta tus credenciales.

2. Cambia el driver activo a MySQL en `.env`:

```bash
ACTIVE_DRIVER=mysql
```

3. Configura credenciales locales en `.env`:

```bash
MYSQL_HOST=127.0.0.1
MYSQL_PORT=3306
MYSQL_DBNAME=apiphp
MYSQL_USER=root
MYSQL_PASSWORD=
```

4. (Opcional) Controla auto-creación y seed:

```bash
MYSQL_AUTO_CREATE_DATABASE=1
MYSQL_INIT_SCHEMA=1
MYSQL_SEED_DEFAULT_USERS=1
```

5. Arranca local con router (importante para Swagger y OpenAPI):

```bash
php -S localhost:8000 router.php
```

Con esos valores, al arrancar la API se conecta a MySQL y crea `products`, `users` y `auth_sessions` si no existen.

### Despliegue en Vercel con Aiven MySQL

Este proyecto ya incluye:

- `vercel.json` para enrutar todo hacia runtime PHP serverless.
- `api/index.php` como entrypoint de Vercel que reutiliza `public/index.php`.

Checklist paso a paso (pantalla por pantalla):

1. **Git provider**
- Sube el proyecto a GitHub/GitLab/Bitbucket con estos archivos incluidos: `vercel.json`, `api/index.php`, `public/index.php`.

2. **Vercel Dashboard > Add New > Project**
- Importa tu repositorio.

3. **Configure Project**
- Framework Preset: `Other`.
- Root Directory: raíz del proyecto.
- Build Command: vacío.
- Output Directory: vacío.

4. **Environment Variables**
- Agrega estas variables (Production, Preview y Development):

```text
ACTIVE_DRIVER=mysql
MYSQL_HOST=<host_aiven>
MYSQL_PORT=3306
MYSQL_DBNAME=<database_aiven>
MYSQL_USER=<user_aiven>
MYSQL_PASSWORD=<password_aiven>
MYSQL_AUTO_CREATE_DATABASE=0
MYSQL_INIT_SCHEMA=1
MYSQL_SEED_DEFAULT_USERS=0
MYSQL_SSL_MODE=verify_identity
MYSQL_SSL_CA=<ruta_o_contenido_ca_si_aplica>
MYSQL_SSL_VERIFY_SERVER_CERT=1
API_KEY=<api_key_segura>
JWT_SECRET=<jwt_secret_seguro>
```

5. **Deploy**
- Haz clic en `Deploy`.

6. **Validación post-deploy**
- Abre `/api/docs/swagger`.
- Abre `/api/docs/openapi.json`.
- Prueba `POST /api/auth/login`.
- Prueba `GET /api/products` con token Bearer.

7. **Logs y troubleshooting en Vercel**
- Si falla conexión DB: revisa `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_USER`, `MYSQL_PASSWORD`.
- Si falla TLS/SSL: revisa `MYSQL_SSL_MODE`, `MYSQL_SSL_CA`, `MYSQL_SSL_VERIFY_SERVER_CERT`.
- Si no quieres seed en producción: usa `MYSQL_SEED_DEFAULT_USERS=0`.

Rutas mínimas a probar:

- `/api/docs/swagger`
- `/api/auth/login`
- `/api/products`

Nota sobre Aiven MySQL:

- Aiven ofrece instancias MySQL compatibles con el driver `mysql` de PDO.
- Si tu usuario de Aiven no tiene permisos para crear esquemas, deja `MYSQL_AUTO_CREATE_DATABASE=0` y crea la base previamente desde el panel de Aiven.

### Guía para principiantes

Si quieres probar la API sin saber PHP, sigue la documentación completa en [docs/GUIDA_PRINCIPIANTES.md](docs/GUIDA_PRINCIPIANTES.md).

---

## 1. ¿Qué hace esta API?

La API expone un servicio RESTful para la gestión de un catálogo de **Productos** (`Product`). Cada producto cuenta con las siguientes propiedades:
- `id` (entero, autoincrementable)
- `name` (cadena de texto, obligatorio)
- `description` (cadena de texto, opcional)
- `price` (número decimal, obligatorio y positivo)
- `created_at` (fecha y hora en formato `YYYY-MM-DD HH:MM:SS`)

### Catálogo de Endpoints

| Método | Endpoint | Descripción | Body (JSON) | Código HTTP |
| :--- | :--- | :--- | :--- | :--- |
| **GET** | `/api/products` | Obtiene la lista completa de productos ordenados por creación. | Ninguno | `200 OK` |
| **GET** | `/api/products/{id}` | Obtiene los detalles de un producto específico. | Ninguno | `200 OK` / `404 Not Found` |
| **POST** | `/api/products` | Registra un nuevo producto (ejecuta validaciones). | `{ name, description, price }` | `201 Created` / `400 Bad Request` |
| **PUT** | `/api/products/{id}` | Realiza una actualización parcial o total del producto. | `{ name?, description?, price? }` | `200 OK` / `400 Bad Request` / `404 Not Found` |
| **DELETE** | `/api/products/{id}` | Elimina físicamente un producto del sistema. | Ninguno | `204 No Content` / `404 Not Found` |
| **POST** | `/api/auth/login` | Autentica usuario y entrega access/refresh token. | `{ email, password }` | `200 OK` / `400 Bad Request` / `401 Unauthorized` |
| **POST** | `/api/auth/refresh` | Renueva tokens usando refresh token. | `{ refresh_token }` | `200 OK` / `400 Bad Request` / `401 Unauthorized` |
| **POST** | `/api/auth/logout` | Revoca el refresh token y cierra sesión. | `{ refresh_token }` | `200 OK` / `400 Bad Request` / `401 Unauthorized` |

---

## 2. Cómo Probar la API

### Levantando el Servidor Local
Para probar la API de forma local, abre la terminal en la raíz del proyecto y ejecuta:
```bash
php -S localhost:8000 router.php
```

Una vez levantado el servidor, puedes abrir directamente:
- http://localhost:8000/api/docs/swagger para la UI de Swagger
- http://localhost:8000/api/docs/openapi.json para la especificación OpenAPI
- http://localhost:8000/api/docs para la documentación JSON del proyecto

---

### Probar con Postman

1. **Crear una nueva petición en Postman**:
   - Para listar: Método `GET`, URL `http://localhost:8000/api/products`
   - Para crear: Método `POST`, URL `http://localhost:8000/api/products`
2. **Configurar los Headers (importante para POST/PUT)**:
   - Añade la cabecera `Content-Type` con el valor `application/json`.
3. **Enviar el Body en POST/PUT**:
   - Selecciona la pestaña **Body**, elige la opción **raw**, y en el desplegable selecciona **JSON**.
   - **Ejemplo de Body en POST**:
     ```json
     {
       "name": "Teclado Mecánico RGB",
       "description": "Teclado mecánico con switches red",
       "price": 85.99
     }
     ```
   - **Ejemplo de Body en PUT (Soporta actualización parcial)**:
     ```json
     {
       "price": 79.99
     }
     ```

---

### Probar con Swagger / OpenAPI

Para usar Swagger, puedes utilizar la especificación estándar de OpenAPI 3.0. A continuación se incluye la definición en formato YAML de esta API.

#### Paso a paso para visualizar y probar en Swagger:
1. Copia el siguiente código YAML.
2. Ve al editor oficial en línea de Swagger: [Swagger Editor (https://editor.swagger.io/)](https://editor.swagger.io/).
3. Pega el código en el panel izquierdo.
4. Podrás visualizar y probar los endpoints interactuando con tu servidor local (asegúrate de que el servidor PHP local `http://localhost:8000` esté activo).

```yaml
openapi: 3.0.3
info:
  title: API RESTful de Productos (SOLID Vanilla PHP)
  description: API CRUD para el catálogo de productos utilizando PHP nativo bajo arquitectura SOLID.
  version: 1.0.0
servers:
  - url: http://localhost:8000
paths:
  /api/products:
    get:
      summary: Obtener todos los productos
      responses:
        '200':
          description: Lista de productos devuelta exitosamente.
          content:
            application/json:
              schema:
                type: array
                items:
                  $ref: '#/components/schemas/Product'
    post:
      summary: Crear un nuevo producto
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required:
                - name
                - price
              properties:
                name:
                  type: string
                  example: Laptop Asus Rog
                description:
                  type: string
                  example: Portátil potente de gaming
                price:
                  type: number
                  format: float
                  example: 1350.00
      responses:
        '201':
          description: Producto creado.
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Product'
        '400':
          description: Datos de entrada inválidos o faltantes.

  /api/products/{id}:
    get:
      summary: Obtener un producto por ID
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: integer
      responses:
        '200':
          description: Detalles del producto.
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Product'
        '404':
          description: Producto no encontrado.
    put:
      summary: Actualizar un producto por ID
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: integer
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                name:
                  type: string
                description:
                  type: string
                price:
                  type: number
                  format: float
      responses:
        '200':
          description: Producto actualizado correctamente.
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Product'
        '404':
          description: Producto no encontrado.
    delete:
      summary: Eliminar un producto por ID
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: integer
      responses:
        '204':
          description: Producto eliminado exitosamente (sin contenido de respuesta).
        '404':
          description: Producto no encontrado.

components:
  schemas:
    Product:
      type: object
      properties:
        id:
          type: integer
          example: 1
        name:
          type: string
          example: Laptop Asus Rog
        description:
          type: string
          example: Portátil potente de gaming
        price:
          type: number
          format: float
          example: 1350.00
        created_at:
          type: string
          example: "2026-06-30 20:00:00"
```

---

## 3. ¿Cómo Cambiar de Base de Datos? (DIP en Acción)

Gracias al **Principio de Inversión de Dependencias (DIP)** de SOLID, el repositorio y los controladores dependen de la interfaz `DatabaseConnectionInterface` en lugar de una base de datos específica (como SQLite). 

Si deseas cambiar de SQLite a **MySQL** o **PostgreSQL**, solo tienes que seguir estos 3 pasos sencillos sin alterar una sola línea de lógica de negocio o controladores.

---

### Paso 1: Configurar las Credenciales en `config.php`

Edita el archivo `config.php` para incluir las credenciales de conexión correspondientes:

```php
<?php

return [
    'database' => [
        'sqlite' => [
            'path' => __DIR__ . '/database.sqlite'
        ],
        'mysql' => [
            'host' => '127.0.0.1',
            'port' => '3306',
            'dbname' => 'apiphp',
            'username' => 'root',
            'password' => ''
        ],
        'pgsql' => [
            'host' => '127.0.0.1',
            'port' => '5432',
            'dbname' => 'apiphp',
            'username' => 'postgres',
            'password' => 'postgres_password'
        ]
    ]
];
```

---

### Paso 2: Crear el Archivo de Conexión Concreto

Crea una clase en `src/Core/Database/` que implemente la interfaz `DatabaseConnectionInterface`.

#### Para MySQL:
Crea el archivo `src/Core/Database/MySQLConnection.php`:

```php
<?php

namespace App\Core\Database;

use PDO;
use Exception;

class MySQLConnection implements DatabaseConnectionInterface
{
    private ?PDO $connection = null;

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $config = require __DIR__ . '/../../../config.php';
            $dbConfig = $config['database']['mysql'] ?? null;

            if (!$dbConfig) {
                throw new Exception("MySQL configuration is missing in config.php");
            }

            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
            
            $this->connection = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Opcional: inicializar tabla si no existe
            $this->initializeSchema();
        }

        return $this->connection;
    }

    private function initializeSchema(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10, 2) NOT NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $this->connection->exec($sql);
    }
}
```

#### Para PostgreSQL:
Crea el archivo `src/Core/Database/PostgreSQLConnection.php`:

```php
<?php

namespace App\Core\Database;

use PDO;
use Exception;

class PostgreSQLConnection implements DatabaseConnectionInterface
{
    private ?PDO $connection = null;

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $config = require __DIR__ . '/../../../config.php';
            $dbConfig = $config['database']['pgsql'] ?? null;

            if (!$dbConfig) {
                throw new Exception("PostgreSQL configuration is missing in config.php");
            }

            $dsn = "pgsql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']}";
            
            $this->connection = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Opcional: inicializar tabla si no existe
            $this->initializeSchema();
        }

        return $this->connection;
    }

    private function initializeSchema(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS products (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10, 2) NOT NULL,
                created_at TIMESTAMP NOT NULL
            );
        ";
        $this->connection->exec($sql);
    }
}
```

---

### Paso 3: Actualizar el Registro de Enlace en `public/index.php`

Modifica el archivo de arranque `public/index.php` para enlazar la nueva conexión en el contenedor de dependencias:

#### Si vas a cambiar a MySQL:
```php
use App\Core\Database\MySQLConnection;

// Register Dependencies (Dependency Inversion Principle)
$container->singleton(DatabaseConnectionInterface::class, MySQLConnection::class);
```

#### Si vas a cambiar a PostgreSQL:
```php
use App\Core\Database\PostgreSQLConnection;

// Register Dependencies (Dependency Inversion Principle)
$container->singleton(DatabaseConnectionInterface::class, PostgreSQLConnection::class);
```
