<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private $tableUser = 'users';
    private $tableCustomer = 'customers';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table($this->tableUser, function (Blueprint $table) {
            $table->string('device_token', 300)->nullable();
        });
        Schema::table($this->tableCustomer, function (Blueprint $table) {
            $table->string('device_token', 300)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropColumns($this->tableCustomer, ['device_token']);
        Schema::dropColumns($this->tableUser, ['device_token']);
    }
};
