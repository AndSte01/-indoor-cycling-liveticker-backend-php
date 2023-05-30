<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('result', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false)->unsigned();
            $table->timestamp('changed')->useCurrentOnUpdate()->useCurrent()->nullable(false);
            $table->foreignId('discipline_id')->nullable()->constrained('discipline')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->text('name');
            $table->text('club');
            $table->float('score_submitted');
            $table->float('score_accomplished');
            $table->tinyInteger('finished');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('result');
    }
};
