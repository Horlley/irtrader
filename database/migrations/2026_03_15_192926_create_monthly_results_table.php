<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMonthlyResultsTable extends Migration
{
    public function up()
    {
        Schema::create('monthly_results', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('year');
            $table->integer('month');

            // RESULTADO DO MÊS
            $table->decimal('profit_daytrade', 15, 2)->default(0);
            $table->decimal('profit_swing', 15, 2)->default(0);
            $table->decimal('profit_futuro', 15, 2)->default(0);

            // PREJUÍZO GERADO NO MÊS
            $table->decimal('loss_daytrade', 15, 2)->default(0);
            $table->decimal('loss_swing', 15, 2)->default(0);
            $table->decimal('loss_futuro', 15, 2)->default(0);

            // PREJUÍZO ACUMULADO ATÉ O MÊS
            $table->decimal('carry_loss_daytrade', 15, 2)->default(0);
            $table->decimal('carry_loss_swing', 15, 2)->default(0);
            $table->decimal('carry_loss_futuro', 15, 2)->default(0);

            // VENDAS (IMPORTANTE PARA ISENÇÃO 20K)
            $table->decimal('total_sales', 15, 2)->default(0);

            // IMPOSTO
            $table->decimal('tax_daytrade', 15, 2)->default(0);
            $table->decimal('tax_swing', 15, 2)->default(0);
            $table->decimal('tax_futuro', 15, 2)->default(0);

            $table->decimal('tax_due', 15, 2)->default(0);

            // DARF
            $table->boolean('darf_generated')->default(false);
            $table->date('darf_due_date')->nullable();
            $table->decimal('darf_value', 15, 2)->nullable();

            $table->timestamps();

            // evita duplicidade do mesmo mês
            $table->unique(['user_id', 'year', 'month']);

        });
    }

    public function down()
    {
        Schema::dropIfExists('monthly_results');
    }
}