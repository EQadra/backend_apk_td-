<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
      Schema::create('doctors', function (Blueprint $table) {
    $table->id();

    $table->foreignId('user_id')->constrained()->onDelete('cascade');

    $table->string('first_name');
    $table->string('last_name');
    $table->text('description')->nullable(); // mejor text
    $table->string('degree')->nullable();
    $table->string('specialty')->nullable();
    $table->string('graduation_code')->nullable();
    $table->string('city')->nullable();
    $table->string('university')->nullable();

    $table->json('services')->nullable();
    $table->float('rating')->default(0);

    $table->string('image')->nullable();
    $table->string('schedule')->nullable();

    $table->timestamps();
});




    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
