<?php

return [

    'model_classes_folder' => app_path("Models"),

    'api' => [
        'key' => [
            'header' => 'x-connect-api-key',
            'value' => ''
        ],
        'prefix' => 'square1/connect',
        'auth' => [
             'model' => 'User'
            ],
    ],
    'clients' => [
        'build_path' => base_path().'/build/square1/connect',
        'android' => [ 'package' => 'com.connect.client' ],
        'ios' => [ 'prefix' => 'cnt' , 'data_model_name' => 'laravel_connect' ],//laravel_connect.xcdatamodeld
    ],

];
