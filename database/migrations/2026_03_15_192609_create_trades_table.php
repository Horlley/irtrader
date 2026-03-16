<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTradesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::create('trades', function (Blueprint $table) {

        $table->id();

        $table->unsignedBigInteger('user_id');

        $table->date('trade_date');

        $table->string('broker'); // xp, clear, rico

        $table->string('asset'); // WIN, WDO, PETR4

        $table->string('market'); // futuro, acao, opcao

        $table->enum('side', ['buy', 'sell']); // compra ou venda

        $table->integer('quantity');

        $table->decimal('price', 12, 2);

        $table->decimal('gross_value', 12, 2)->nullable();

        $table->decimal('fees', 12, 2)->nullable();

        $table->decimal('net_result', 12, 2)->nullable();

        $table->string('trade_type'); // daytrade ou swing

        $table->string('source_file')->nullable(); // pdf enviado

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
        Schema::dropIfExists('trades');
    }
}
