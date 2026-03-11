<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            // FK a users (autor del post)
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->string('title');
            $table->text('content')->nullable();
            $table->string('image')->nullable();
            $table->string('category')->nullable();

            // 🔹 Polimórfico: Doctor, Lawyer, Association, Shop
$table->morphs('postable');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
