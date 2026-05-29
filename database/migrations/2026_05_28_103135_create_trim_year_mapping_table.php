<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trim_year_mapping', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('year');
            $table->unsignedBigInteger('trim_mapping_id');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();

            $table->foreign('trim_mapping_id')->references('id')->on('trim_mapping');
            $table->unique(['trim_mapping_id', 'year'], 'uq_trim_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trim_year_mapping');
    }
};
