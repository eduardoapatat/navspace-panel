<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Administrador inicial
    |--------------------------------------------------------------------------
    |
    | Account the seeder creates on the first deployment so the panel can be
    | accessed right away. The example values are meant for a local run and
    | should be replaced through the environment on a real deployment.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME') ?: 'Administrador NavSpace',
        'email' => env('ADMIN_EMAIL') ?: 'test@example.com',
        'password' => env('ADMIN_PASSWORD') ?: 'password',
    ],

];
