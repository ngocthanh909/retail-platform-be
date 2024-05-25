<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('notifications', 'notification_delivery');
        Schema::rename('notification_template', 'notifications');
    }
    public function down(): void
    {
    }
};
