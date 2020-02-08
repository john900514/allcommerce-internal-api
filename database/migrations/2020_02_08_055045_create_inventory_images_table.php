<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_images', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();

            $table->uuid('merchant_uuid'); //AC Linked
            $table->uuid('inventory_uuid'); //AC Linked
            $table->string('platform_id')->nullable(); //Shopify Linked
            $table->string('platform')->default('allcommerce');
            $table->string('inventory_platform_id')->nullable(); //Shopify inventory id

            $table->integer('position')->nullable();
            $table->string('alt')->nullable();
            $table->string('width')->nullable();
            $table->string('height')->nullable();
            $table->string('src')->nullable();
            $table->longText('variant_ids')->nullable();

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
        Schema::dropIfExists('inventory_images');
    }
}
