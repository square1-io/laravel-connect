# Laravel Connect

Access data of a laravel app via a REST API.

## Installation

```sh
$ composer require square1ltd/laravel-connect
```
## Setup 

### Register the service in the app config
Square1\Laravel\Connect\ConnectServiceProvider::class

### Update the Schema Facade in the app config
    Replace 
        'Schema' => Illuminate\Support\Facades\Schema::class  
    with    
        'Schema' => Square1\Laravel\Connect\Model\ConnectSchema::class

Double check that the migration files are using the Schema facade  

### Add authorization guards 

        'guards' => [
            'connect' => [
                'driver' => 'passport',
                'provider' => 'users',
            ],
        ],

## Config
```sh
$ php artisan connect:init
```

## ENV

CONNECT_API_KEY: api key
CONNECT_API_AUTH_CLIENT_ID laravel passport client id
CONNECT_API_AUTH_GRANT_TYPE laravel passport grant type ( default is password)
CONNECT_API_AUTH_CLIENT_SECRET laravel passport secret 
