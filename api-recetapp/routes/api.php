<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\SuperAdminController;

// Rutas Públicas
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/activate/{token}', [AuthController::class, 'activateAccount']);
Route::post('/activate-account', [AuthController::class, 'activateAccountForm']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Rutas Protegidas (requieren Sanctum token)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Datos principales
    Route::get('/get_all', [ApiController::class, 'getAll']);
    Route::post('/update_profile', [ApiController::class, 'updateProfile']);
    Route::get('/admin_stats', [ApiController::class, 'adminStats']);

    // Consejos rotativos
    Route::get('/tips', function () {
        $file = database_path('tips.json');
        if (!file_exists($file)) {
            return response()->json(['tips' => []]);
        }
        $tips = json_decode(file_get_contents($file), true);
        return response()->json(['tips' => $tips ?? []]);
    });

    // Usuarios
    Route::post('/invite_user', [ApiController::class, 'inviteUser']);
    Route::post('/resend_invitation', [ApiController::class, 'resendInvitation']);
    Route::post('/delete_user', [ApiController::class, 'deleteUser']);

    // Recetas y Planning
    Route::post('/save_recipe', [ApiController::class, 'saveRecipe']);
    Route::post('/delete_recipe', [ApiController::class, 'deleteRecipe']);
    Route::post('/save_planning', [ApiController::class, 'savePlanning']);

    // Ingredientes
    Route::post('/save_ingredient', [ApiController::class, 'saveIngredient']);
    Route::post('/delete_ingredient', [ApiController::class, 'deleteIngredient']);

    // Compras
    Route::post('/update_shopping', [ApiController::class, 'updateShopping']);
    Route::post('/toggle_shopping_item', [ApiController::class, 'toggleShoppingItem']);
    Route::post('/delete_shopping_item', [ApiController::class, 'deleteShoppingItem']);
    Route::post('/add_shopping_item', [ApiController::class, 'addShoppingItem']);
    Route::post('/add_recipe_to_shopping', [ApiController::class, 'addRecipeToShopping']);

    // Uploads
    Route::post('/upload/profile-photo', [ApiController::class, 'uploadProfilePhoto']);
    Route::post('/upload/recipe-image', [ApiController::class, 'uploadRecipeImage']);
    Route::post('/delete_profile_photo', [ApiController::class, 'deleteProfilePhoto']);
    Route::post('/delete_recipe_image', [ApiController::class, 'deleteRecipeImage']);
});

// Rutas protegidas solo para SuperAdmin
Route::middleware(['auth:sanctum', 'superadmin'])->prefix('admin')->group(function () {
    Route::post('/load-predefined', [SuperAdminController::class, 'loadPredefined']);
    Route::post('/delete-predefined', [SuperAdminController::class, 'deletePredefined']);
    Route::post('/clear-cache', [SuperAdminController::class, 'clearCache']);
});
