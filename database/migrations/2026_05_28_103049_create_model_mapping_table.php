<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_mapping', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurer_id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('dr_make_id');
            $table->unsignedBigInteger('dr_model_id');
            $table->string('logo_path', 255)->nullable();
            $table->string('insurer_model_id', 50);
            $table->string('insurer_model_name', 100);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();

            $table->foreign('insurer_id')->references('id')->on('insurers');
            $table->foreign('category_id')->references('id')->on('category');
            $table->unique(['insurer_id', 'dr_model_id'], 'uq_co_model_mapping');
            $table->index('dr_model_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_mapping');
    }
};
