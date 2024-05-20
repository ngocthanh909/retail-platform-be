<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private $table = 'categories';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table($this->table, function(Blueprint $table){
            $table->double('commission_rate', 3, 1, true)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if(Schema::hasColumn($this->table, 'commission_rate')){
            Schema::dropColumns($this->table, 'commission_rate');
        };
    }
};
