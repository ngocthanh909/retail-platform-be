<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private $table = 'orders';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table($this->table, function(Blueprint $table){
            $table->double('total_commission', 11, 2, true)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if(Schema::hasColumns($this->table, ['total_commission'])){
            Schema::dropColumns($this->table, ['total_commission']);
        };
    }
};
