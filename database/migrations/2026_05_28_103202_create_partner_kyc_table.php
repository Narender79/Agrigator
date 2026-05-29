<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_kyc', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->string('gstin', 20)->nullable();
            $table->text('pan_number');                 // Encrypted
            $table->text('bank_account_no');            // Encrypted
            $table->string('bank_ifsc', 20);
            $table->string('cancelled_cheque_path', 255);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();

            $table->foreign('partner_id')->references('id')->on('partners');
            $table->index('partner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_kyc');
    }
};
