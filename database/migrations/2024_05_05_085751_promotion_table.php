<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private $tableName = 'promotions';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->id();
            $table->string("name", 300);
            $table->string("code", 100)->unique()->nullable();
            $table->string("description", 300)->nullable();
            $table->integer("qty")->default(1);
            $table->integer("used")->default(0);
            $table->datetime("start_date");
            $table->datetime("end_date");
            $table->tinyInteger("apply")->default(0)->comment("0: all, 1: specific");

            $table->tinyInteger('promote_by')->default();
            $table->tinyInteger('promote_type')->default();
            $table->unsignedDecimal('promote_min_order_price', 11, 2);

            $table->unsignedInteger("gift_product_id")->nullable();
            $table->unsignedInteger("gift_product_qty")->nullable();


            $table->unsignedInteger("discount_value")->nullable();
            $table->unsignedInteger("discount_type")->nullable();


            $table->tinyInteger("status");
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
