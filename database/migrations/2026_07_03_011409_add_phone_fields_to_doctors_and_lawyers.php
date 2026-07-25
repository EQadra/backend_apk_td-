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
        // Agregar campo phone a doctors
        Schema::table('doctors', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('schedule');
            $table->string('emergency_phone')->nullable()->after('phone');
            $table->string('clinic_phone')->nullable()->after('emergency_phone');
        });

        // Agregar campo phone a lawyers
        Schema::table('lawyers', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('schedule');
            $table->string('office_phone')->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn(['phone', 'emergency_phone', 'clinic_phone']);
        });

        Schema::table('lawyers', function (Blueprint $table) {
            $table->dropColumn(['phone', 'office_phone']);
        });
    }
};