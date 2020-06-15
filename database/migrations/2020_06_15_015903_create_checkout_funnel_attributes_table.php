<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCheckoutFunnelAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('checkout_funnel_attributes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('funnel_uuid');

            $table->string('funnel_attribute');
            $table->string('funnel_value')->nullable();
            $table->longText('funnel_misc_json')->nullable();

            $table->boolean('active')->default(1);
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
        Schema::dropIfExists('checkout_funnel_attributes');
    }
}
