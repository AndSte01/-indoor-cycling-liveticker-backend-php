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
        Schema::create('discipline', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false)->unsigned();
            $table->timestamp('changed')->useCurrentOnUpdate()->useCurrent()->nullable(false);
            $table->foreignId('competition_id')->nullable()->constrained('competitions')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->tinyInteger('type')->nullable(false)->default(-1);
            $table->text('fallback_name')->nullable();
            $table->tinyInteger('area')->nullable()->default(1);
            $table->tinyInteger('round')->unsigned()->nullable()->default(0);
            $table->tinyInteger('finished')->nullable()->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discipline');
    }
};
