<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $tableName = 'orders';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('responsible_staff')->nullable();
            $table->unsignedBigInteger('creator')->nullable();
            $table->string('customer_name', 255);
            $table->string('phone', 20);
            $table->string('province', 100);
            $table->string('district', 100);
            $table->string('address', 300);
            $table->unsignedDouble('subtotal')->default(0);
            $table->unsignedDouble('total')->default(0);
            $table->string('discount_code')->nullable();
            $table->string('discount')->default(0);
            $table->string('discount_note', 300)->nullable();
            $table->string('note', 500)->nullable();
            $table->tinyInteger('status')->default(1)->comment('1: Chờ xác nhận, 2: Đã xác nhận, 3: Hoàn thành, 0: Đã hủy');
            $table->timestamps();
        });;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::dropIfExists($this->tableName);
    }
};
