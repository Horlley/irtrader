<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoteIdToTradesTable extends Migration
{

    public function up()
    {

        Schema::table('trades', function (Blueprint $table) {

            $table->unsignedBigInteger('note_id')->nullable()->after('user_id');

            $table->index('note_id');

            $table->foreign('note_id')
                ->references('id')
                ->on('broker_notes')
                ->onDelete('cascade');

        });

    }


    public function down()
    {

        Schema::table('trades', function (Blueprint $table) {

            $table->dropForeign(['note_id']);

            $table->dropIndex(['note_id']);

            $table->dropColumn('note_id');

        });

    }

}