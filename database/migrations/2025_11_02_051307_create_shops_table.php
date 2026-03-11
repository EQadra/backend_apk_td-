<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
Schema::create('shops', function (Blueprint $table) {
    $table->id();

    $table->foreignId('user_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->string('name');
    $table->text('description')->nullable();
    $table->string('address', 255)->nullable();
    $table->string('city', 100)->nullable();
    $table->string('phone', 20)->nullable();
    $table->string('image')->nullable();
    $table->string('schedule')->nullable();

    $table->timestamps();
});

    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
