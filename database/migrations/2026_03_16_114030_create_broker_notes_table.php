igration completa

Arquivo:

database/migrations/xxxx_xx_xx_create_broker_notes_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBrokerNotesTable extends Migration
{
    public function up()
    {

        Schema::create('broker_notes', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('user_id');

            $table->string('broker', 50);

            $table->string('note_number', 50);

            $table->date('trade_date')->nullable();

            $table->string('source_file')->nullable();

            $table->decimal('total_value', 15, 2)->nullable();

            $table->decimal('total_fees', 15, 2)->nullable();

            $table->timestamps();

            // índice para consultas rápidas
            $table->index('user_id');

            // impedir duplicação da nota
            $table->unique(['user_id','note_number']);

        });

    }

    public function down()
    {
        Schema::dropIfExists('broker_notes');
    }
}