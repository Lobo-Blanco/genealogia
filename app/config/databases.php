<?php
/**
 * KumbiaPHP Web Framework
 * Parámetros de conexión a la base de datos
 */
return [
    'default' => [
        /**
         * username: usuario con permisos en la base de datos
         */
        'username' => 'root', //no es recomendable usar el usuario root
        /**
         * password: clave del usuario de la base de datos
         */
        'password' => '',
        /**
         * dsn: Cadena de conexión a la base de datos
         */
        'dsn'      => 'mysql:host=127.0.0.1;dbname=genealogia;charset=utf8',
        'params'   => [
            \PDO::ATTR_PERSISTENT => true,
            \PDO::ATTR_ERRMODE    => \PDO::ERRMODE_EXCEPTION
        ],
    ],

    'development' => [
        /**
         * host: ip o nombre del host de la base de datos
         */
        'host'     => 'localhost',
        /**
         * username: usuario con permisos en la base de datos
         */
        'username' => 'root', //no es recomendable usar el usuario root
        /**
         * password: clave del usuario de la base de datos
         */
        'password' => '',
        /**
         * test: nombre de la base de datos
         */
        'name'     => 'genealogia',
        /**
         * type: tipo de motor de base de datos (mysql, pgsql o sqlite)
         */
        'type'     => 'mysql',
        /**
         * charset: Conjunto de caracteres de conexión, por ejemplo 'utf8'
         */
        'charset'  => 'utf8',
        /**
         * dsn: cadena de conexión a la base de datos
         */
        //'dsn' => '',
        /**
         * pdo: activar conexiones PDO (OnOff); descomentar para usar
         */
        //'pdo' => 'On',
        ],
];

/**
 * Ejemplo de SQLite
 */
/*'development' => [
    'type' => 'sqlite',
    'dsn' => 'temp/data.sq3',
    'pdo' => 'On',
] */
