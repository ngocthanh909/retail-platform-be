<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $tableName = 'products';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->string('description', 1000)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::dropColumns($this->tableName, 'description');
    }
};
