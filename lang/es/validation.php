<?php

// Mensajes de validación en español para las reglas que usa Monoverse.
// El inglés se toma de los mensajes por defecto de Laravel.
return [
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'decimal' => 'El campo :attribute debe tener :decimal decimales.',
    'email' => 'El campo :attribute debe ser un correo electrónico válido.',
    'exists' => 'El valor seleccionado en :attribute no es válido.',
    'image' => 'El campo :attribute debe ser una imagen.',
    'in' => 'El valor seleccionado en :attribute no es válido.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'lte' => [
        'numeric' => 'El campo :attribute debe ser menor o igual que :value.',
    ],
    'max' => [
        'file' => 'El archivo :attribute no debe superar :max kilobytes.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe tener más de :max caracteres.',
    ],
    'mimes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'min' => [
        'file' => 'El archivo :attribute debe tener al menos :min kilobytes.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'numeric' => 'El campo :attribute debe ser un número.',
    'password' => [
        'letters' => 'El campo :attribute debe contener al menos una letra.',
        'mixed' => 'El campo :attribute debe contener al menos una mayúscula y una minúscula.',
        'numbers' => 'El campo :attribute debe contener al menos un número.',
        'symbols' => 'El campo :attribute debe contener al menos un símbolo.',
        'uncompromised' => 'El :attribute indicado apareció en una filtración de datos. Elige otro.',
    ],
    'regex' => 'El formato de :attribute no es válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'required_if' => 'El campo :attribute es obligatorio cuando :other es :value.',
    'string' => 'El campo :attribute debe ser texto.',
    'unique' => 'El :attribute ya está en uso.',
    'uploaded' => 'No se pudo subir :attribute.',

    'attributes' => [
        'amount' => 'monto',
        'base_price' => 'precio base',
        'description' => 'descripción',
        'duration_hours' => 'duración',
        'email' => 'correo electrónico',
        'handle' => 'alias',
        'image' => 'imagen',
        'name' => 'nombre',
        'palette_from' => 'color inicial',
        'palette_to' => 'color final',
        'password' => 'contraseña',
        'price' => 'precio',
        'quantity' => 'piezas a acuñar',
        'total_supply' => 'suministro total',
        'type' => 'modalidad',
    ],
];
