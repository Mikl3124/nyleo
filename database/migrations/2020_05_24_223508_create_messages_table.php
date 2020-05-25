<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('from_id')->index();
            $table->foreign('from_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('to_id')->index();
            $table->foreign('to_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('projet_id')->nullable();
            $table->foreign('projet_id')->references('id')->on('projets')->onDelete('cascade');
            $table->text('content')->nullable();;
            $table->timestamps();
            $table->string('file_message')->nullable();
            $table->dateTime('read_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messages_table');
    }
}
