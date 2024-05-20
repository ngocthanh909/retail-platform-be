<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private $table = 'order_details';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table($this->table, function(Blueprint $table){
            $table->unsignedBigInteger('category_id');
            $table->string('category_name');
            $table->double('category_commission_rate', 3, 1, true)->default(0);
            $table->double('category_commission_amount', 11, 2, true)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if(Schema::hasColumns($this->table, ['category_commission_rate', 'category_name', 'category_id', 'category_commission_amount'])){
            Schema::dropColumns($this->table, ['category_commission_rate', 'category_name', 'category_id', 'category_commission_amount']);
        };
    }
};
