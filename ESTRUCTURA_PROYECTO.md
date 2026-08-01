# Estructura del Proyecto Explicada para Principiantes

Este documento explica la estructura real del proyecto con un lenguaje simple. La idea es que, aunque estes empezando en PHP, puedas entender que hace cada carpeta, que responsabilidad tiene cada archivo importante y como fluye una peticion desde que entra a la API hasta que sale la respuesta.

## 1. Idea general del proyecto

Este proyecto es una API REST hecha en PHP sin frameworks grandes como Laravel o Symfony. Eso significa que muchas piezas que en otros proyectos vienen "automaticas" aqui fueron construidas a mano para aprender mejor como funciona una aplicacion por dentro.

La API hace principalmente estas cosas:

- Gestiona productos: listar, ver uno, crear, actualizar y eliminar.
- Maneja autenticacion: login, refresh de token y logout.
- Protege endpoints con API key o JWT.
- Permite probar la API desde Swagger/OpenAPI.
- Puede trabajar con SQLite, MySQL o PostgreSQL.

## 2. Como pensar la arquitectura

Una forma simple de entender el proyecto es dividirlo en capas:

- `public/`: la puerta de entrada.
- `src/Core/`: las herramientas base del sistema.
- `src/Application/`: la logica de uso de la aplicacion.
- `src/Domain/`: las reglas del negocio y sus contratos.
- `src/Infrastructure/`: la parte que habla con la base de datos.
- `docs/`: guias para personas que van a probar o estudiar la API.
- `tests/`: comprobaciones simples para validar comportamiento.

Dicho de otra manera:

- El usuario hace una peticion.
- La peticion entra por `public/index.php`.
- El `Router` decide a que controlador enviarla.
- El controlador llama a un servicio.
- El servicio usa repositorios para leer o guardar datos.
- El repositorio habla con la base de datos.
- La respuesta vuelve al usuario en JSON o HTML.

## 3. Flujo de una peticion paso a paso

Si alguien llama por ejemplo a `POST /api/products`, el recorrido es este:

1. El servidor recibe la peticion.
2. `router.php` redirige todo a `public/index.php` cuando la ruta no es un archivo fisico.
3. `public/index.php` carga configuracion, crea el contenedor, registra rutas y ejecuta middleware.
4. `AuthMiddleware` verifica si la ruta es publica o si necesita autenticacion.
5. `Router.php` encuentra el controlador y metodo correctos.
6. `ProductController.php` recibe la peticion.
7. `CreateProductDTO.php` valida y ordena los datos de entrada.
8. `ProductService.php` aplica la logica de negocio.
9. `PDOProductRepository.php` guarda o consulta la informacion.
10. `Response.php` construye la respuesta final para el cliente.

Ese flujo es importante porque te ayuda a ubicar donde mirar cuando algo falla.

## 4. Estructura real del proyecto

```text
api-php/
├── composer.json
├── composer.lock
├── config.php
├── database.sqlite
├── ESTRUCTURA_PROYECTO.md
├── README.md
├── router.php
├── docs/
│   └── GUIDA_PRINCIPIANTES.md
├── logs/
├── public/
│   ├── .htaccess
│   └── index.php
├── src/
│   ├── Application/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── DocsController.php
│   │   │   └── ProductController.php
│   │   ├── Docs/
│   │   │   └── ApiDocumentationService.php
│   │   ├── DTO/
│   │   │   ├── CreateProductDTO.php
│   │   │   ├── LoginDTO.php
│   │   │   ├── RefreshTokenDTO.php
│   │   │   └── UpdateProductDTO.php
│   │   ├── Middlewares/
│   │   │   └── AuthMiddleware.php
│   │   └── Services/
│   │       ├── AuthenticationService.php
│   │       ├── AuthService.php
│   │       ├── LoginRateLimiter.php
│   │       ├── ProductService.php
│   │       └── RoleService.php
│   ├── Core/
│   │   ├── Container.php
│   │   ├── Logger.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── Router.php
│   │   ├── Database/
│   │   │   ├── DatabaseConnectionInterface.php
│   │   │   ├── MySQLConnection.php
│   │   │   ├── PostgreSQLConnection.php
│   │   │   └── SQLiteConnection.php
│   │   └── Exceptions/
│   │       └── HttpException.php
│   ├── Domain/
│   │   ├── Entities/
│   │   │   └── Product.php
│   │   └── Repositories/
│   │       ├── AuthSessionRepositoryInterface.php
│   │       ├── ProductRepositoryInterface.php
│   │       └── UserRepositoryInterface.php
│   └── Infrastructure/
│       └── Persistence/
│           ├── PDOAuthSessionRepository.php
│           ├── PDOProductRepository.php
│           ├── PDOUserRepository.php
│           └── ProductMapper.php
├── storage/
├── tests/
│   └── ApiDocumentationServiceTest.php
└── vendor/
```

## 5. Archivos de la raiz

### `composer.json`
Es el archivo donde PHP define dependencias y reglas de autoload. En este proyecto le dice a Composer que la carpeta `src/` corresponde al namespace `App\`.

### `composer.lock`
Guarda las versiones exactas instaladas. Sirve para que otra persona descargue exactamente las mismas dependencias.

### `config.php`
Contiene configuracion importante como:

- driver de base de datos activo
- claves de autenticacion
- parametros del rate limit
- datos de conexion

Es un archivo central porque cambia el comportamiento del proyecto sin tocar la logica principal.

### `database.sqlite`
Es la base de datos local cuando se usa SQLite. Es util para aprender porque no necesitas instalar un motor aparte.

### `README.md`
Es la guia principal del proyecto: como levantar el servidor, como probar endpoints, que usuarios existen y donde abrir Swagger.

### `router.php`
Es un router para el servidor integrado de PHP. Hace que rutas como `/api/products` o `/api/docs/swagger` no fallen aunque no existan como archivos fisicos.

## 6. Carpeta `public/`

Esta carpeta representa la entrada web del proyecto.

### `public/.htaccess`
Se usa cuando corres la API con Apache. Redirige las peticiones al punto de entrada principal.

### `public/index.php`
Es el archivo mas importante del arranque. Hace varias tareas:

- carga Composer
- lee `config.php`
- crea el `Container`
- registra conexiones y servicios
- registra las rutas
- ejecuta middleware
- captura errores y responde al cliente

Si quieres entender como se ensambla toda la aplicacion, este es uno de los primeros archivos que debes leer.

## 7. Carpeta `src/Core/`

Aqui viven las piezas base del sistema. Son herramientas generales, no reglas del negocio.

### `Container.php`
Es un contenedor de dependencias. Su trabajo es crear objetos y entregarles automaticamente lo que necesitan.

Ejemplo mental:

- `ProductController` necesita `ProductService`
- `ProductService` necesita `ProductRepositoryInterface`
- el contenedor resuelve todo eso sin que tu lo construyas a mano en cada archivo

### `Logger.php`
Guarda errores en `logs/app.log`. Es una forma simple de dejar registro cuando algo falla en produccion o durante pruebas.

### `Request.php`
Representa la peticion HTTP actual. Lee cosas como:

- metodo HTTP: `GET`, `POST`, `PUT`, `DELETE`
- URI
- headers
- query params
- body JSON

En vez de usar `$_SERVER`, `$_POST` o `php://input` por todas partes, el proyecto concentra eso aqui.

### `Response.php`
Construye la respuesta HTTP. Permite devolver JSON, HTML y respuestas vacias con codigos de estado y headers.

### `Router.php`
Compara la URL con las rutas registradas y decide que controlador debe ejecutarse. Tambien soporta parametros como `/api/products/{id}`.

### `Database/DatabaseConnectionInterface.php`
Es un contrato para las conexiones de base de datos. Define la idea de que cualquier conexion debe poder entregar un acceso utilizable por el resto del sistema.

### `Database/SQLiteConnection.php`
Crea la conexion SQLite, prepara tablas si no existen y suele ser la opcion mas simple para desarrollo local.

### `Database/MySQLConnection.php`
Hace lo mismo que la conexion SQLite, pero para MySQL.

### `Database/PostgreSQLConnection.php`
Hace lo mismo, pero adaptado a PostgreSQL.

### `Exceptions/HttpException.php`
Es una excepcion personalizada para errores HTTP controlados, por ejemplo un `401` o un `404`. Ayuda a lanzar errores con un codigo claro en lugar de usar mensajes genericos.

## 8. Carpeta `src/Domain/`

Aqui esta el corazon del negocio. Esta capa intenta describir que es el sistema sin depender de detalles tecnicos como SQL o HTTP.

### `Entities/Product.php`
Representa un producto del dominio. Normalmente una entidad contiene datos importantes y reglas de validez relacionadas con ese objeto.

### `Repositories/ProductRepositoryInterface.php`
Define que operaciones se pueden hacer con productos, por ejemplo buscar, listar, guardar, actualizar o eliminar. No dice como hacerlo, solo que debe existir esa capacidad.

### `Repositories/UserRepositoryInterface.php`
Define como buscar usuarios, especialmente para login y validacion de roles.

### `Repositories/AuthSessionRepositoryInterface.php`
Define como manejar sesiones de autenticacion basadas en refresh tokens: crear sesion, revocar sesion, buscar una activa y limpiar expiradas.

## 9. Carpeta `src/Infrastructure/`

Esta capa implementa la parte tecnica concreta. Si `Domain` dice "necesito un repositorio", aqui vive la clase que realmente lo hace.

### `Persistence/PDOProductRepository.php`
Implementa el acceso real a productos usando PDO y consultas SQL.

### `Persistence/PDOUserRepository.php`
Busca usuarios en la base de datos. Se usa especialmente en login para verificar email, rol y estado del usuario.

### `Persistence/PDOAuthSessionRepository.php`
Guarda y revoca sesiones de refresh token. Esto permite que `logout` no sea solo "borrar algo del cliente", sino invalidar de verdad la sesion en el servidor.

### `Persistence/ProductMapper.php`
Convierte filas crudas de base de datos en objetos `Product` o en estructuras mas limpias para el resto de la aplicacion. Un mapper evita mezclar demasiada logica de transformacion dentro del repositorio.

## 10. Carpeta `src/Application/`

Esta capa orquesta casos de uso reales. Es donde las peticiones del usuario se convierten en acciones concretas.

### `Controllers/`
Los controladores reciben peticiones y devuelven respuestas.

#### `AuthController.php`
Maneja:

- login
- refresh token
- logout

Tambien captura errores comunes y devuelve respuestas HTTP adecuadas.

#### `ProductController.php`
Maneja el CRUD de productos. Suele ser la capa que conecta `Request`, DTOs, servicios y `Response`.

#### `DocsController.php`
Expone endpoints de documentacion como:

- `/api/docs`
- `/api/docs/openapi.json`
- `/api/docs/swagger`

Gracias a este controlador puedes explorar la API en navegador.

### `Docs/`
Contiene servicios relacionados con documentacion.

#### `ApiDocumentationService.php`
Construye la documentacion propia de la API y tambien la especificacion OpenAPI que consume Swagger UI.

### `DTO/`
Los DTO son objetos para transportar y validar datos de entrada.

#### `CreateProductDTO.php`
Recoge y valida los datos necesarios para crear un producto.

#### `UpdateProductDTO.php`
Recoge y valida los datos permitidos para actualizar un producto.

#### `LoginDTO.php`
Valida email y password del login.

#### `RefreshTokenDTO.php`
Valida el token recibido en refresh y logout.

### `Middlewares/`
Los middlewares se ejecutan antes de llegar al controlador principal.

#### `AuthMiddleware.php`
Hace varias tareas importantes:

- deja pasar rutas publicas como login o docs
- exige autenticacion en rutas protegidas
- acepta `Authorization: Bearer ...` o `X-API-Key`
- revisa permisos de escritura para crear, editar o borrar productos

Este archivo es clave para entender seguridad.

### `Services/`
Los servicios contienen logica de aplicacion y de negocio.

#### `ProductService.php`
Se encarga de coordinar operaciones con productos. Normalmente no sabe de HTTP; sabe de casos de uso como crear o actualizar.

#### `AuthService.php`
Se centra en la generacion y lectura de tokens, API key y utilidades relacionadas con autenticacion.

#### `AuthenticationService.php`
Coordina el flujo completo de autenticacion:

- login
- refresh
- logout
- limpieza de sesiones expiradas
- persistencia de sesiones con refresh token

Es mas alto nivel que `AuthService.php`.

#### `RoleService.php`
Traduce roles a permisos concretos. Por ejemplo:

- `admin` puede leer y escribir productos
- `editor` puede leer y escribir productos
- `viewer` solo puede leer

#### `LoginRateLimiter.php`
Evita ataques de fuerza bruta limitando intentos fallidos de login por IP. Guarda su estado en `storage/login_rate_limit.json`.

## 11. Carpeta `docs/`

### `docs/GUIDA_PRINCIPIANTES.md`
Es una guia pensada para alguien que quiere probar la API aunque aun no domine PHP. Suele enfocarse mas en uso practico que en arquitectura interna.

## 12. Carpeta `tests/`

### `tests/ApiDocumentationServiceTest.php`
Es una prueba simple para verificar que la documentacion OpenAPI se genera correctamente. Aunque no es una suite completa, sirve para validar rapidamente una parte sensible del proyecto.

## 13. Carpetas auxiliares

### `logs/`
Aqui se guardan archivos de log, por ejemplo errores registrados por `Logger.php`.

### `storage/`
Se usa para datos auxiliares persistentes del sistema. En este proyecto guarda, entre otras cosas, el archivo del rate limit de login.

### `vendor/`
Es la carpeta generada por Composer. Aqui viven dependencias externas y el autoload. Normalmente no se edita manualmente.

## 14. Que archivo leer primero si estas aprendiendo

Si estas empezando, este orden te va a ayudar mucho:

1. `README.md` para entender el objetivo general.
2. `public/index.php` para ver como arranca todo.
3. `src/Core/Request.php`, `Response.php` y `Router.php` para entender la base web.
4. `src/Application/Controllers/ProductController.php` para ver una peticion real.
5. `src/Application/Services/ProductService.php` para entender la logica.
6. `src/Infrastructure/Persistence/PDOProductRepository.php` para ver el acceso a datos.
7. `src/Application/Controllers/AuthController.php` y `src/Application/Services/AuthenticationService.php` para entender autenticacion.
8. `src/Application/Middlewares/AuthMiddleware.php` para entender permisos.

## 15. Como ubicar errores segun el tipo de problema

Si algo falla, esta guia rapida te puede orientar:

- Si una URL no responde: revisa `router.php`, `public/index.php` y `src/Core/Router.php`.
- Si falla el login: revisa `AuthController.php`, `AuthenticationService.php`, `AuthService.php` y `PDOUserRepository.php`.
- Si falla un permiso: revisa `AuthMiddleware.php` y `RoleService.php`.
- Si falla una consulta a base de datos: revisa la conexion activa en `config.php` y luego el repositorio correspondiente.
- Si Swagger no carga: revisa `DocsController.php`, `ApiDocumentationService.php` y `router.php`.
- Si el login se bloquea demasiado: revisa `LoginRateLimiter.php` y `storage/login_rate_limit.json`.

## 16. Resumen final

Este proyecto esta dividido para que cada archivo tenga una responsabilidad clara. Esa separacion ayuda a:

- entender mejor el codigo
- cambiar una pieza sin romper todo
- probar cada parte con mas facilidad
- aprender arquitectura en PHP de forma mas ordenada

Si lo estudias por capas y no como un bloque gigante, te resultara mucho mas facil comprenderlo.
