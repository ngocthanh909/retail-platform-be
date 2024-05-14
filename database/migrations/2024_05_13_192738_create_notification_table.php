<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private $tableName = 'notifications';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists($this->tableName);
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('user_type')->default(1);
            $table->unsignedBigInteger('receiver_id');
            $table->unsignedBigInteger('template_id');
            $table->dateTime('delivery_time')->nullable();
            $table->tinyInteger('seen')->default(0);
            $table->tinyInteger('sent')->default(0);
            $table->timestamps();
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
