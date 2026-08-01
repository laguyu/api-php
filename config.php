<?php

require_once __DIR__ . '/bootstrap/load_env.php';

return [
    // =========================================================================
    // CONFIGURACIÓN DE BASE DE DATOS ACTIVA
    // =========================================================================
    // Escribe aquí qué base de datos deseas usar. Opciones válidas:
    // - 'sqlite' : Usa un archivo de base de datos local (Ideal para pruebas rápidas, no requiere credenciales).
    // - 'mysql'  : Usa un servidor de base de datos MySQL (por ejemplo, Laragon, XAMPP, phpMyAdmin).
    // - 'pgsql'  : Usa un servidor de base de datos PostgreSQL.
    // -------------------------------------------------------------------------
    'active_driver' => (($_ENV['ACTIVE_DRIVER'] ?? $_SERVER['ACTIVE_DRIVER'] ?? getenv('ACTIVE_DRIVER')) ?: 'mysql'),

    'auth' => [
        'api_key' => ($_ENV['API_KEY'] ?? $_SERVER['API_KEY'] ?? getenv('API_KEY')) ?: '',
        'jwt_secret' => ($_ENV['JWT_SECRET'] ?? $_SERVER['JWT_SECRET'] ?? getenv('JWT_SECRET')) ?: '',
        'rate_limit' => [
            'max_attempts' => (int) (($_ENV['LOGIN_MAX_ATTEMPTS'] ?? $_SERVER['LOGIN_MAX_ATTEMPTS'] ?? getenv('LOGIN_MAX_ATTEMPTS')) ?: 5),
            'window_seconds' => (int) (($_ENV['LOGIN_WINDOW_SECONDS'] ?? $_SERVER['LOGIN_WINDOW_SECONDS'] ?? getenv('LOGIN_WINDOW_SECONDS')) ?: 900),
            'block_seconds' => (int) (($_ENV['LOGIN_BLOCK_SECONDS'] ?? $_SERVER['LOGIN_BLOCK_SECONDS'] ?? getenv('LOGIN_BLOCK_SECONDS')) ?: 900),
        ],
    ],

    'database' => [
        // Configuración para SQLite (Por defecto)
        'sqlite' => [
            'path' => __DIR__ . '/database.sqlite'
        ],

        // Configuración para MySQL
        'mysql' => [
            'host' => ($_ENV['MYSQL_HOST'] ?? $_SERVER['MYSQL_HOST'] ?? getenv('MYSQL_HOST')) ?: '127.0.0.1',
            'port' => ($_ENV['MYSQL_PORT'] ?? $_SERVER['MYSQL_PORT'] ?? getenv('MYSQL_PORT')) ?: '3306',
            'dbname' => ($_ENV['MYSQL_DBNAME'] ?? $_SERVER['MYSQL_DBNAME'] ?? getenv('MYSQL_DBNAME')) ?: 'apiphp',
            'username' => ($_ENV['MYSQL_USER'] ?? $_SERVER['MYSQL_USER'] ?? getenv('MYSQL_USER')) ?: 'root',
            'password' => ($_ENV['MYSQL_PASSWORD'] ?? $_SERVER['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD')) ?: '',
            'auto_create_database' => in_array(strtolower((string) (($_ENV['MYSQL_AUTO_CREATE_DATABASE'] ?? $_SERVER['MYSQL_AUTO_CREATE_DATABASE'] ?? getenv('MYSQL_AUTO_CREATE_DATABASE')) ?: '1')), ['1', 'true', 'yes', 'on'], true),
            'init_schema' => in_array(strtolower((string) (($_ENV['MYSQL_INIT_SCHEMA'] ?? $_SERVER['MYSQL_INIT_SCHEMA'] ?? getenv('MYSQL_INIT_SCHEMA')) ?: '1')), ['1', 'true', 'yes', 'on'], true),
            'seed_default_users' => in_array(strtolower((string) (($_ENV['MYSQL_SEED_DEFAULT_USERS'] ?? $_SERVER['MYSQL_SEED_DEFAULT_USERS'] ?? getenv('MYSQL_SEED_DEFAULT_USERS')) ?: '1')), ['1', 'true', 'yes', 'on'], true),
            'ssl_mode' => ($_ENV['MYSQL_SSL_MODE'] ?? $_SERVER['MYSQL_SSL_MODE'] ?? getenv('MYSQL_SSL_MODE')) ?: '',
            'ssl_ca' => ($_ENV['MYSQL_SSL_CA'] ?? $_SERVER['MYSQL_SSL_CA'] ?? getenv('MYSQL_SSL_CA')) ?: '',
            'ssl_verify_server_cert' => !in_array(strtolower((string) (($_ENV['MYSQL_SSL_VERIFY_SERVER_CERT'] ?? $_SERVER['MYSQL_SSL_VERIFY_SERVER_CERT'] ?? getenv('MYSQL_SSL_VERIFY_SERVER_CERT')) ?: '1')), ['0', 'false', 'no', 'off'], true),
        ],

        // Configuración para PostgreSQL
        'pgsql' => [
            'host' => ($_ENV['PGSQL_HOST'] ?? $_SERVER['PGSQL_HOST'] ?? getenv('PGSQL_HOST')) ?: '127.0.0.1',
            'port' => ($_ENV['PGSQL_PORT'] ?? $_SERVER['PGSQL_PORT'] ?? getenv('PGSQL_PORT')) ?: '5432',
            'dbname' => ($_ENV['PGSQL_DBNAME'] ?? $_SERVER['PGSQL_DBNAME'] ?? getenv('PGSQL_DBNAME')) ?: 'apiphp',
            'username' => ($_ENV['PGSQL_USER'] ?? $_SERVER['PGSQL_USER'] ?? getenv('PGSQL_USER')) ?: 'postgres',
            'password' => ($_ENV['PGSQL_PASSWORD'] ?? $_SERVER['PGSQL_PASSWORD'] ?? getenv('PGSQL_PASSWORD')) ?: 'postgres_password'
        ]
    ]
];
