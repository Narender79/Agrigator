<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_login_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->uuid('device_id')->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('os_name', 50)->nullable();
            $table->string('app_version', 20)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('long', 11, 8)->nullable();
            $table->string('browser_name', 100)->nullable();
            $table->timestamps();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->foreign('user_id')->references('id')->on('users');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_login_attempts');
    }
};
