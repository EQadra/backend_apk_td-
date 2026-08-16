<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();

            // Usuario que creó la noticia
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Perfil asociado opcionalmente:
            // Doctor, Lawyer, Shop o Association
            $table->nullableMorphs('newable');

            $table->string('titulo', 191);
            $table->text('descripcion')->nullable();
            $table->string('url', 191)->nullable();
            $table->string('image', 191)->nullable();
            $table->dateTime('fecha_publicacion')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};