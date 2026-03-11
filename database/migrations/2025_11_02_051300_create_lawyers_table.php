<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
Schema::create('lawyers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('first_name');
    $table->string('last_name');
    $table->text('description')->nullable();
    $table->string('schedule', 100)->nullable(); // ✅ solo una
    $table->string('specialty')->nullable();
    $table->string('license_code')->nullable();
    $table->string('city')->nullable();
    $table->string('university')->nullable();
    $table->string('image')->nullable();
    $table->timestamps();
});

    }

    public function down(): void
    {
        Schema::dropIfExists('lawyers');
    }
};
