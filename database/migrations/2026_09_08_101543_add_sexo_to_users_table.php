<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar a users
        if (!Schema::hasColumn('users', 'sexo')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('sexo', ['masculino', 'femenino', 'lgbt', 'otro', 'no_especificado'])
                      ->default('no_especificado')
                      ->after('city');
            });
        }

        // Agregar a doctors
        if (Schema::hasTable('doctors') && !Schema::hasColumn('doctors', 'sexo')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->enum('sexo', ['masculino', 'femenino', 'lgbt', 'otro', 'no_especificado'])
                      ->default('no_especificado')
                      ->after('clinic_phone');
            });
        }

        // Agregar a lawyers
        if (Schema::hasTable('lawyers') && !Schema::hasColumn('lawyers', 'sexo')) {
            Schema::table('lawyers', function (Blueprint $table) {
                $table->enum('sexo', ['masculino', 'femenino', 'lgbt', 'otro', 'no_especificado'])
                      ->default('no_especificado')
                      ->after('office_phone');
            });
        }

        // Agregar a shops
        if (Schema::hasTable('shops') && !Schema::hasColumn('shops', 'sexo')) {
            Schema::table('shops', function (Blueprint $table) {
                $table->enum('sexo', ['masculino', 'femenino', 'lgbt', 'otro', 'no_especificado'])
                      ->default('no_especificado')
                      ->after('schedule');
            });
        }

        // Agregar a associations
        if (Schema::hasTable('associations') && !Schema::hasColumn('associations', 'sexo')) {
            Schema::table('associations', function (Blueprint $table) {
                $table->enum('sexo', ['masculino', 'femenino', 'lgbt', 'otro', 'no_especificado'])
                      ->default('no_especificado')
                      ->after('website');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sexo');
        });

        if (Schema::hasTable('doctors')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->dropColumn('sexo');
            });
        }

        if (Schema::hasTable('lawyers')) {
            Schema::table('lawyers', function (Blueprint $table) {
                $table->dropColumn('sexo');
            });
        }

        if (Schema::hasTable('shops')) {
            Schema::table('shops', function (Blueprint $table) {
                $table->dropColumn('sexo');
            });
        }

        if (Schema::hasTable('associations')) {
            Schema::table('associations', function (Blueprint $table) {
                $table->dropColumn('sexo');
            });
        }
    }
};