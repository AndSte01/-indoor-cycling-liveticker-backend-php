<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var string Name of the Table to modify */
    const TABLENAME = 'users';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(self::TABLENAME, function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false)->unsigned();
            $table->tinyText('name')->nullable(false);
            $table->integer('password_hash'); // get's changed to VARBINARY(64) later on
            $table->integer('password_salt'); // get's changed to VARBINARY(64) later on
            $table->timestamp('binary_timestamp')->nullable(true)->default(NULL);
            $table->integer('binary_token'); // get's changed to VARBINARY(64) later on
        });

        // change column of password hash to correct type
        DB::statement('ALTER TABLE ' . self::TABLENAME . ' MODIFY password_hash VARBINARY(64);');
        // change column of password salt to correct type
        DB::statement('ALTER TABLE ' . self::TABLENAME . ' MODIFY password_salt VARBINARY(64);');
        // change column of binary token to correct type
        DB::statement('ALTER TABLE ' . self::TABLENAME . ' MODIFY binary_token VARBINARY(64);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
