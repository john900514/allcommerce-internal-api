<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShopIdToMerchantInventory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('merchant_inventories', function (Blueprint $table) {
            $table->dropColumn('merchant_uuid');
        });

        Schema::table('merchant_inventories', function (Blueprint $table) {
            $table->uuid('merchant_uuid')->nullable()->after('uuid');
            $table->uuid('shop_install_id')->nullable()->after('merchant_uuid');
            $table->boolean('default_item')->default(0)->after('tags');
            $table->boolean('active')->default(0)->after('default_item');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('merchant_inventories', function (Blueprint $table) {
            $table->dropColumn('shop_install_id');
            $table->dropColumn('default_item');
            $table->dropColumn('active');
        });
    }
}
