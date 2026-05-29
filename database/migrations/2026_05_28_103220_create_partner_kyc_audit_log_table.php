<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_kyc_audit_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->string('action', 50);               // kyc_uploaded, kyc_approved, kyc_rejected
            $table->unsignedBigInteger('performed_by'); // FK -> users.id (Super Admin performing verify)
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('partner_id')->references('id')->on('partners');
            $table->foreign('performed_by')->references('id')->on('users');
            $table->index('partner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_kyc_audit_log');
    }
};
