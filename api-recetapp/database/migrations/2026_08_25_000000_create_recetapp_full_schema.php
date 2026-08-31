<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('shopping_items');
        Schema::dropIfExists('plannings');
        Schema::dropIfExists('recipe_ingredient');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('activation_tokens');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('houses');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('migrations');

        Schema::create('migrations', function (Blueprint $table) {
            $table->string('migration');
            $table->integer('batch');
        });

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        Schema::create('houses', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('nombre_casa');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('nombre');
            $table->string('password');
            $table->string('foto', 500)->nullable();
            $table->string('casa_id')->nullable();
            $table->string('role')->default('user');
            $table->string('status')->default('active');
            $table->foreign('casa_id')->references('id')->on('houses')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('activation_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('token', 64)->unique();
            $table->timestamps();
        });

        Schema::create('ingredients', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('casa_id')->nullable();
            $table->foreign('casa_id')->references('id')->on('houses')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('nombre');
            $table->text('pasos');
            $table->string('imagen', 500)->nullable();
            $table->string('casa_id')->nullable();
            $table->foreign('casa_id')->references('id')->on('houses')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('recipe_ingredient', function (Blueprint $table) {
            $table->id();
            $table->string('recipe_id');
            $table->string('ingredient_id');
            $table->string('quantity');
            $table->foreign('recipe_id')->references('id')->on('recipes')->onDelete('cascade');
            $table->foreign('ingredient_id')->references('id')->on('ingredients')->onDelete('cascade');
        });

        Schema::create('plannings', function (Blueprint $table) {
            $table->id();
            $table->string('casa_id');
            $table->string('day');
            $table->string('meal');
            $table->string('recipe_id')->nullable();
            $table->foreign('casa_id')->references('id')->on('houses')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('shopping_items', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('casa_id');
            $table->string('text');
            $table->boolean('checked')->default(false);
            $table->foreign('casa_id')->references('id')->on('houses')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopping_items');
        Schema::dropIfExists('plannings');
        Schema::dropIfExists('recipe_ingredient');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('activation_tokens');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('houses');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('migrations');
    }
};
