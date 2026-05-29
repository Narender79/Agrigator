<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_config', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->string('webhook_url', 500)->nullable();
            $table->string('callback_secret', 255)->nullable();
            $table->string('config_type', 100)->default('WEBHOOK_INTEGRATION');
            $table->timestamps();

            $table->foreign('partner_id')->references('id')->on('partners');
            $table->index('partner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_config');
    }
};
