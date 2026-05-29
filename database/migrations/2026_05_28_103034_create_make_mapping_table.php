<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('make_mapping', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurer_id'); // Normalized from insurer_company_id
            $table->unsignedBigInteger('dr_make_id'); // Droom Canonical Make ID
            $table->string('insurer_make_id', 50);    // Insurer-specific make code
            $table->string('insurer_make_name', 100);
            $table->string('logo_path', 255)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_popular')->default(false);
            $table->timestamps();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();

            $table->foreign('insurer_id')->references('id')->on('insurers');
            $table->unique(['insurer_id', 'dr_make_id'], 'uq_co_make_mapping');
            $table->index('dr_make_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('make_mapping');
    }
};
