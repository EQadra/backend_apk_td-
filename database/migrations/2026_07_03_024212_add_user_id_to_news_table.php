<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            // Hacer newable_id y newable_type nullable
            $table->foreignId('newable_id')->nullable()->change();
            $table->string('newable_type')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->foreignId('newable_id')->nullable(false)->change();
            $table->string('newable_type')->nullable(false)->change();
        });
    }
};