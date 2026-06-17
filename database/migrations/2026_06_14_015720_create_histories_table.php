<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
            Schema::create('histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('historyable_id');
            $table->string('historyable_type');

            $table->unsignedInteger('views')
                ->default(1);

            $table->timestamp('last_viewed_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'historyable_type',
                'historyable_id'
            ]);

            $table->index([
                'user_id',
                'last_viewed_at'
            ]);

            $table->unique([
                'user_id',
                'historyable_type',
                'historyable_id'
            ], 'history_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('histories');
    }
};