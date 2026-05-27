<?php

use App\Modelo\Usuario;

/*
|--------------------------------------------------------------------------
| SISARST - Configuracion de autenticacion
|--------------------------------------------------------------------------
|
| El proveedor apunta al modelo Usuario (tabla "usuario"), no al modelo
| User que trae Laravel por defecto, porque la base de datos ya estaba
| creada segun el modelo entidad-relacion del documento de diseno.
|
| La recuperacion de contrasena (HU-17) corresponde al Sprint 4; aqui solo
| queda declarada la configuracion base.
|
*/

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'usuarios'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'usuarios',
        ],
    ],

    'providers' => [
        'usuarios' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', Usuario::class),
        ],
    ],

    'passwords' => [
        'usuarios' => [
            'provider' => 'usuarios',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 30,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
