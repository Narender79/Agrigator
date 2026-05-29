<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurer_integrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurer_id');
            $table->string('api_base_url', 500);
            $table->enum('auth_type', ['OAUTH2', 'BASIC_AUTH', 'API_KEY']);
            $table->text('client_id')->nullable();       // Encrypted
            $table->text('client_secret')->nullable();   // Encrypted
            $table->string('environment', 50)->default('sandbox'); // sandbox, production
            $table->json('other_attributes')->nullable();
            $table->timestamps();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();

            $table->foreign('insurer_id')->references('id')->on('insurers');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurer_integrations');
    }
};
