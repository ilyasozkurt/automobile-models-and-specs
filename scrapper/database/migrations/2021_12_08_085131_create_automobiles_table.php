<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAutomobilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('automobiles', function (Blueprint $table) {
            $table->id();
            $table->string('url_hash')->index('url_hash');
            $table->longText('url');
            $table->bigInteger('brand_id')->index('brand_id');
            $table->string('name')->index('name');
            $table->longText('description')->nullable();
            $table->longText('press_release')->nullable();
            $table->longText('photos')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('automobiles');
    }
}
