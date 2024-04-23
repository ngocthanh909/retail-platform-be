<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $tableName = 'customers';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique();
            $table->string('password', 60);
            $table->string('email', 50)->nullable();
            $table->string('customer_name', 200);
            $table->bigInteger('responsible_staff')->nullable();
            $table->string('address', 100);
            $table->string('district', 50);
            $table->string('province', 50);
            $table->tinyInteger('status')->comment('0 - Disabled, 1 Enabled');
            $table->timestamps();

            $table->index('responsible_staff');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::dropIfExists($this->tableName);
    }
};
