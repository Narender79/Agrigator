<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Link to their Partner Admin user account
            $table->string('business_name', 255);
            $table->enum('partner_type', ['AGENT', 'DEALER', 'RESELLER'])->default('AGENT');
            $table->string('contact_email', 255);
            $table->string('contact_person_name', 255);
            $table->string('contact_person_mobile', 15);
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->timestamps();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
