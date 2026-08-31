<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La tabla ya dispone de created_at/updated_at desde la migración base.
        // Rellenamos created_at en las filas existentes para poder calcular la expiración del token.
        DB::table('activation_tokens')
            ->whereNull('created_at')
            ->update(['created_at' => now()]);
    }

    public function down(): void
    {
        // Sin cambios necesarios.
    }
};