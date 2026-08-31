<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'Recetapp API',
        'environment' => env('APP_ENV', 'production'),
        'description' => 'API Recetapp.',
        'author' => 'Carlos Manuel de la Cruz Romero',
        'app' => env('FRONTEND_URL', 'http://localhost:4200'),
        'debug' => env('APP_DEBUG', false),
    ], 200);
});
