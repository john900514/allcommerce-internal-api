<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopifyInstallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopify_installs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->uuid('nonce')->unique();
            $table->string('shopify_store_url')->nullable();
            $table->string('shopify_store_vanity_url')->nullable();
            $table->uuid('merchant_uuid')->nullable();

            $table->string('auth_code')->nullable();
            $table->string('access_token')->nullable();
            $table->string('scopes')->nullable();

            $table->boolean('installed')->default(0);
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
        Schema::dropIfExists('shopify_installs');
    }
}
