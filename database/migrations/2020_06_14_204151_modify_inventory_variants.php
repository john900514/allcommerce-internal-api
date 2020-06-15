<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyInventoryVariants extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inventory_variants', function (Blueprint $table) {
            $table->dropColumn('merchant_uuid');
            $table->dropColumn('platform_id');
            $table->dropColumn('platform');
            $table->dropColumn('inventory_platform_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inventory_variants', function (Blueprint $table) {
            //
        });
    }
}
