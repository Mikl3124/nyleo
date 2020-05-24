<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projets', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('user_id')->index()->references('id')->on('users')->onDelete('cascade')->nullable();
            $table->string('description')->nullable();
            $table->string('address')->nullable();
            $table->string('cp')->nullable();
            $table->string('town')->nullable();
            $table->string('section')->nullable();
            $table->string('number')->nullable();
            $table->string('superficie')->nullable();
            $table->boolean('multiple_parcelles')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projets');
    }
}
