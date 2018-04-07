<?php

return [
    'app'        => [
        'name'      => getenv('APP_NAME'),
        'env'       => getenv('APP_ENV', 'development'),
        'lang'      => 'en-US',
        'paths'     => [
            'root' => getenv('APP_DIR'),
        ],
        'meta'      => [
            'charset'     => 'utf-8',
            'titlePrefix' => '${app.name} | ',
            'title'       => 'Welcome',
            'titleSuffix' => '',
        ],
        'polyfills' => [
            'Promise',
            'Object.assign',
            'Object.values',
            'Array.prototype.find',
            'Array.prototype.findIndex',
            'Array.prototype.includes',
            'String.prototype.includes',
            'String.prototype.startsWith',
            'String.prototype.endsWith',
        ],
    ],
    'webservice' => [
        'paths' => [
            'root'   => getenv('WEBSERVICE_ROOT_PATH'),
            'public' => getenv('WEBSERVICE_PUBLIC_PATH'),
        ],
    ],
    'client'     => [
        'paths' => [
            'root'   => getenv('CLIENT_ROOT_PATH'),
            'public' => getenv('CLIENT_PUBLIC_PATH'),
        ],
    ],
    'kernel'     => [
        'database' => [
            'paths'         => [
                'migrations' => __DIR__ . '/app/*/*/etc/db/migrations',
                'seeds'      => __DIR__ . '/app/*/*/etc/db/seeds',
            ],
            'environments'  => [
                'default_migration_table' => 'schema_migration_log',
                'default_database'        => 'development',
                'development'             => [
                    'driver'    => getenv('DATABASE_DRIVER', 'mysql'), // -> adapter
                    'host'      => getenv('DATABASE_HOST', 'localhost'), // +
                    'database'  => getenv('DATABASE_NAME', 'rosem'), // -> name
                    'username'  => getenv('DATABASE_USERNAME', 'root'), // -> user
                    'password'  => getenv('DATABASE_PASSWORD', ''), // -> pass
                    'port'      => getenv('DATABASE_PORT'),
                    'charset'   => getenv('DATABASE_CHARSET', 'utf-8'), // +
                    'engine'    => getenv('DATABASE_ENGINE'), // ???
                    'collation' => getenv('DATABASE_COLLATION', 'utf8_unicode_ci'), // ?
                    'prefix'    => getenv('DATABASE_PREFIX', ''), // ?
                ],
            ],
            'version_order' => 'creation',
        ],
    ],
    'backOffice'      => [
        'uri'              => 'bo',
        'meta'             => [
            'titlePrefix' => '${app.name} back-office | ',
            'title'       => 'Dashboard',
            'titleSuffix' => '',
        ],
        'username'         => getenv('ADMIN_USERNAME', 'admin'),
        'password'         => getenv('ADMIN_PASSWORD', 'admin'),
        'session_lifetime' => getenv('ADMIN_SESSION_LIFETIME', 3000),
    ],
    'blog'       => [
        'uri' => 'blog',
    ],
];
