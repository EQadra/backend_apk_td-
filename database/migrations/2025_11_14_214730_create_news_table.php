<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
       Schema::create('news', function (Blueprint $table) {
        $table->id();

        // campos polimórficos:
        $table->morphs('newable'); // genera newable_id y newable_type

        $table->string('titulo');
        $table->text('descripcion')->nullable();
        $table->string('url')->nullable();
        $table->dateTime('fecha_publicacion')->nullable();

        $table->timestamps();
    });

    }

    public function down()
    {
        Schema::dropIfExists('news');
    }
};
