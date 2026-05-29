<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_credentials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->string('api_key', 64)->unique();
            $table->string('api_secret_hash', 255);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('rate_limit_tier', 50)->default('standard'); // standard, premium, custom
            $table->timestamps();

            $table->foreign('partner_id')->references('id')->on('partners');
            $table->index('partner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_credentials');
    }
};
