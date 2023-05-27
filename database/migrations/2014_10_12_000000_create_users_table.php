<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('firstName');
            $table->string('lastName');
            $table->string('country');
            $table->string('city');
            $table->string('street');
            $table->integer('zipCode');
            $table->string('activationCode', 10)->nullable();
            $table->dateTime('codeLife')->nullable();
            $table->boolean('status')->default(0);
            $table->timestamps(); // Dodaje pola 'updated_at' i 'created_at'
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
