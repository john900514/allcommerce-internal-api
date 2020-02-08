<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryVariantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_variants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();

            $table->uuid('merchant_uuid'); //AC Linked
            $table->uuid('inventory_uuid'); //AC Linked
            $table->string('platform_id')->nullable(); //Shopify Linked
            $table->string('platform')->default('allcommerce');

            $table->string('inventory_platform_id')->nullable(); //Shopify inventory id
            $table->string('inventory_item_id')->nullable(); //Shopify variant id

            $table->string('title');
            $table->float('price',8,2)->nullable();
            $table->string('sku')->nullable();
            $table->integer('position')->nullable();

            $table->string('inventory_policy')->nullable();
            $table->string('compare_at_price')->nullable();
            $table->string('fulfillment_service')->nullable();
            $table->string('inventory_management')->nullable();
            $table->string('taxable')->nullable();
            $table->string('barcode')->nullable();
            $table->string('grams')->nullable();
            $table->string('image_id')->nullable();
            $table->string('weight')->nullable();
            $table->string('weight_unit')->nullable();

            $table->integer('inventory_quantity')->nullable();
            $table->integer('old_inventory_quantity')->nullable();
            $table->string('tax_code')->nullable();
            $table->boolean('requires_shipping')->default(0);

            $table->longText('options');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventory_variants');
    }
}
