<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('services', 'image')) {
            Schema::table('services', function (Blueprint $table) {
                $table->string('image')->nullable()->after('duration');
            });
            echo "✅ Columna 'image' agregada a tabla 'services'\n";
        } else {
            echo "ℹ️ Columna 'image' ya existe en 'services'\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('services', 'image')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('image');
            });
            echo "🗑️ Columna 'image' eliminada de 'services'\n";
        }
    }
};