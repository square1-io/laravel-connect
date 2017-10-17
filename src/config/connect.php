<?php

return [

    'model_classes_folder' => app_path("Models"),

    'api' => [
        'prefix' => 'square1/connect',
        'auth' => [
             'model' => 'User'
            ],
    ],
    'clients' => [
        'build_path' => base_path().'/build/square1/connect',
        'android' => [ 'package' => 'com.connect.client' ],
        'ios' => [ 'data_model_name' => 'laravel_connect' ],//laravel_connect.xcdatamodeld
    ],

];
