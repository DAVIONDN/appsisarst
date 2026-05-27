<?php

/*
|--------------------------------------------------------------------------
| SISARST - Ubicacion de la capa Vista
|--------------------------------------------------------------------------
|
| Por convencion Laravel busca las plantillas Blade en resources/views.
| En este proyecto la capa Vista vive en app/Vista, junto a app/Modelo y
| app/Controlador, para que la arquitectura MVC quede explicita en el
| arbol de directorios. La carpeta resources/ ya no se usa.
|
| Si en el futuro se instala un paquete que publique sus propias vistas
| con "php artisan vendor:publish", agregue aqui resource_path('views')
| como segunda ruta de busqueda y cree esa carpeta.
|
*/

return [

    'paths' => [
        app_path('Vista'),
    ],

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),

];
