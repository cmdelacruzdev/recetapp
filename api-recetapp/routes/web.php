<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'Recetapp API',
        'environment' => env('APP_ENV'),
        'description' => 'API Recetapp.',
        'author' => 'Carlos Manuel de la Cruz Romero',
        'app' => env('FRONTEND_URL'),
        'debug' => env('APP_DEBUG'),
    ], 200);
});

// Ruta temporal de mantenimiento para producción
Route::get('/limpiar-todo', function () {
    try {
        // 1. Limpiamos todas las cachés
        Artisan::call('optimize:clear');
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        
        return "<p>Caché borrada y rutas limpiadas.</p>";
    } catch (\Exception $e) {
        return "Hubo un error: " . $e->getMessage();
    }
});
