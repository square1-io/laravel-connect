<?php

Route::group(
    [], function () {
        Route::get('/{model}', [ 'uses' => 'ApiModelController@index', 'as' => 'index']);
        Route::post('/{model}', [ 'uses' =>  'ApiModelController@create', 'as' => 'create']);
        Route::get('/{model}/{id}', [ 'uses' =>  'ApiModelController@show', 'as' => 'show'])->where('id', '[0-9]+');
        Route::post('/{model}/{id}', [ 'uses' =>  'ApiModelController@update', 'as' => 'update'])->where('id', '[0-9]+');
        Route::delete('/{model}/{id}', [ 'uses' =>  'ApiModelController@destroy', 'as' => 'destroy'])->where('id', '[0-9]+');
        Route::get('/{model}/{id}/{relation}', [ 'uses' =>  'ApiModelController@indexRelation', 'as' => 'index_relation'])->where(['id' => '[0-9,]+']);
        Route::get('/{model}/{id}/{relation}/{relId}', [ 'uses' =>  'ApiModelController@showRelation', 'as' => 'show_relation'])->where(['id' => '[0-9]+','relId' => '[0-9]+']);
    
        Route::post('/{model}/{id}/{relation}', [ 'uses' =>  'ApiModelController@updateRelation', 'as' => 'update_relation'])->where(['id' => '[0-9]+']);
    
        Route::delete('/{model}/{id}/{relation}/{relationId}', [ 'uses' =>  'ApiModelController@deleteRelation', 'as' => 'delete_relation'])->where(['id' => '[0-9]+','relId' => '[0-9]+']);

        Route::group(
            ['prefix' => 'auth', 'as' => 'auth.'], function () {
                Route::get('/current', [ 'uses' => 'AuthController@show', 'as' => 'current']);
                Route::post('/login', [ 'uses' => 'AuthController@login', 'as' => 'login']);
                Route::post('/reset-password', [ 'uses' => 'AuthController@login', 'as' => 'reset-password']);
                Route::post('/register', [ 'uses' => 'AuthController@register', 'as' => 'register']);
                Route::post('/connect', [ 'uses' => 'AuthController@connect', 'as' => 'connect']);
            }
        );
    
        Route::options('{all}', [ 'uses' => 'ConnectBaseController@options', 'as' => 'options'])->where('all', '.*');
    }
);
