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
        Schema::create('competitions', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);
            $table->timestamp('changed')->useCurrentOnUpdate()->nullable(false);
            $table->foreignId('user_id')->nullable()->constrained('users')
                ->onDelete('set null')->onUpdate('cascade');
            $table->tinyInteger('feature_set');
            $table->text('name');
            $table->text('location');
            $table->tinyInteger('areas');
            $table->tinyInteger('live');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
