<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trim_mapping', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurer_id'); // Normalized from insurer_company_id
            $table->unsignedBigInteger('dr_make_id');
            $table->unsignedBigInteger('dr_model_id');
            $table->unsignedBigInteger('dr_trim_id');
            $table->string('insurer_trim_id', 50);
            $table->string('insurer_trim_name', 100);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();

            $table->foreign('insurer_id')->references('id')->on('insurers');
            $table->unique(['insurer_id', 'dr_trim_id'], 'uq_co_trim_mapping');
            $table->index('dr_trim_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trim_mapping');
    }
};
