<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private $table = 'notification_campaigns';
    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->string('title', 300);
            $table->string('content', 1000);
            $table->string('image', 300);
            $table->time('delivery_time')->nullable();
            $table->date('delivery_date')->nullable();
            $table->string('repeat', 50)->default('none')->comment('none|weekday:monday|everyday');
            $table->dateTime('next_repeat')->nullable();
            $table->integer('receiver_id')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
};
