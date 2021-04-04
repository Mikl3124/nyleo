<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvantprojetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('avantprojets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('projet_id')->index();
            $table->foreign('projet_id')->references('id')->on('projets')->onDelete('cascade');
            $table->boolean('accepted')->default(false);
            $table->string('url');
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
        Schema::dropIfExists('avantprojets');
    }
}
