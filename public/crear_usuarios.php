<?php

$usuarios = [
    [
        'nombre' => 'Administrador',
        'apellidos' => 'del sistema',
        'username' => 'admin',
        'password' => 'admin123',
        'email' => 'admin@genealogia.local',
        'rol_id' => 1,
        'persona_referencia_id' => null,
    ],

    [
        'nombre' => 'Usuario',
        'apellidos' => 'Visitante',
        'username' => 'visita',
        'password' => 'visita123',
        'email' => 'visita@genealogia.local',
        'rol_id' => 2,
        'persona_referencia_id' => null,
    ],

    [
        'nombre' => 'Carlos',
        'apellidos' => 'García Martínez',
        'username' => 'carlos',
        'password' => 'carlos123',
        'email' => 'carlos@genealogia.local',
        'rol_id' => 3,
        'persona_referencia_id' => 8,
    ],

    [
        'nombre' => 'Marta',
        'apellidos' => 'García Fernández',
        'username' => 'marta',
        'password' => 'marta123',
        'email' => 'marta@genealogia.local',
        'rol_id' => 3,
        'persona_referencia_id' => 6,
    ],
];

$pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=genealogia;charset=utf8',
    'root',
    ''
);

$pdo->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

$sql = '
    INSERT INTO usuarios
        (
            nombre,
            apellidos,
            username,
            password,
            email,
            rol_id,
            persona_referencia_id,
            activo,
            created_at,
            updated_at
        )
    VALUES
        (
            :nombre,
            :apellidos,
            :username,
            :password,
            :email,
            :rol_id,
            :persona_referencia_id,
            1,
            NOW(),
            NOW()
        )
';

$stmt = $pdo->prepare($sql);

foreach ($usuarios as $usuario) {

    $stmt->execute([
        ':nombre' =>
            $usuario['nombre'],

        ':apellidos' =>
            $usuario['apellidos'],

        ':username' =>
            $usuario['username'],

        ':password' =>
            password_hash(
                $usuario['password'],
                PASSWORD_DEFAULT
            ),

        ':email' =>
            $usuario['email'],

        ':rol_id' =>
            $usuario['rol_id'],

        ':persona_referencia_id' =>
            $usuario['persona_referencia_id'],
    ]);

    echo 'Creado: '
       . $usuario['username']
       . '<br>';
}