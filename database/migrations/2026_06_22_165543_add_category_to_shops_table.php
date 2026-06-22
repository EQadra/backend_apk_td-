// database/migrations/xxxx_xx_xx_add_category_to_shops_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('category')->nullable()->after('name');
        });
    }

    public function down()
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};