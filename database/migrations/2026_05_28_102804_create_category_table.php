<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category', function (Blueprint $table) {
            $table->id();
            $table->string('category_name', 100);
            $table->enum('type', ['2w', '4w']);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('icon', 255)->nullable();
            $table->string('category_image', 255)->nullable();
            $table->string('category_logo', 255)->nullable();
            $table->timestamps();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category');
    }
};
