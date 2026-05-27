<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SISARST - Mensajes de validacion en espanol
|--------------------------------------------------------------------------
|
| Solo se traducen las reglas que el Sprint 1 utiliza. Los mensajes
| especificos de cada campo se definen en los FormRequest correspondientes.
|
*/

return [
    'accepted' => 'El campo :attribute debe ser aceptado.',
    'after' => 'El campo :attribute debe ser una fecha posterior a :date.',
    'after_or_equal' => 'El campo :attribute debe ser una fecha posterior o igual a :date.',
    'before' => 'El campo :attribute debe ser una fecha anterior a :date.',
    'before_or_equal' => 'El campo :attribute debe ser una fecha anterior o igual a :date.',
    'between' => [
        'array' => 'El campo :attribute debe tener entre :min y :max elementos.',
        'file' => 'El campo :attribute debe pesar entre :min y :max kilobytes.',
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'string' => 'El campo :attribute debe tener entre :min y :max caracteres.',
    ],
    'confirmed' => 'La confirmacion de :attribute no coincide.',
    'current_password' => 'La contrasena es incorrecta.',
    'date' => 'El campo :attribute no es una fecha valida.',
    'date_format' => 'El campo :attribute no corresponde al formato :format.',
    'different' => 'Los campos :attribute y :other deben ser diferentes.',
    'digits' => 'El campo :attribute debe tener :digits digitos.',
    'digits_between' => 'El campo :attribute debe tener entre :min y :max digitos.',
    'email' => 'El campo :attribute no es un correo electronico valido.',
    'exists' => 'El valor seleccionado en :attribute no es valido.',
    'filled' => 'El campo :attribute es obligatorio.',
    'in' => 'El valor seleccionado en :attribute no es valido.',
    'integer' => 'El campo :attribute debe ser un numero entero.',
    'max' => [
        'array' => 'El campo :attribute no debe tener mas de :max elementos.',
        'file' => 'El campo :attribute no debe pesar mas de :max kilobytes.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe tener mas de :max caracteres.',
    ],
    'min' => [
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
        'file' => 'El campo :attribute debe pesar al menos :min kilobytes.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'numeric' => 'El campo :attribute debe ser un numero.',
    'regex' => 'El formato del campo :attribute no es valido.',
    'required' => 'El campo :attribute es obligatorio.',
    'required_if' => 'El campo :attribute es obligatorio cuando :other es :value.',
    'same' => 'Los campos :attribute y :other deben coincidir.',
    'string' => 'El campo :attribute debe ser texto.',
    'unique' => 'El valor de :attribute ya se encuentra registrado.',
    'url' => 'El campo :attribute no es una URL valida.',

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    'attributes' => [
        'dni' => 'DNI',
        'nombres' => 'nombres',
        'apellidos' => 'apellidos',
        'cargo' => 'cargo',
        'area_id' => 'area',
        'horario_id' => 'horario',
        'condicion_laboral' => 'condicion laboral',
        'telefono' => 'telefono',
        'correo' => 'correo electronico',
        'fecha_ingreso' => 'fecha de ingreso',
        'motivo_baja' => 'motivo de la baja',
        'username' => 'usuario',
        'password' => 'contrasena',
    ],
];
