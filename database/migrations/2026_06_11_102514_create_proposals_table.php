<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->onDelete('set null');
            $table->unsignedBigInteger('policy_id')->nullable(); // Can link to insurance_policies table later

            // Proposer Personal Details
            $table->string('salutation', 10); // Mr, Mrs, Ms, Dr, M/s
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('full_name', 255);
            $table->date('dob');
            $table->integer('age')->nullable();
            $table->string('gender', 15); // Male, Female, Other
            $table->string('marital_status', 20); // single, married, divorced, widowed
            $table->string('nationality', 50)->default('Indian');
            $table->string('occupation', 50); // salaried, self_employed, business, student, retired, homemaker, other
            $table->string('email', 150);
            $table->string('mobile', 15);

            // Proposer Address Details (Required for KYC & Policy issuance)
            $table->string('address_line_1', 255);
            $table->string('address_line_2', 255)->nullable();
            $table->string('pincode', 10);
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();

            // Nominee Details (Crucial for Motor Insurance)
            $table->string('nominee_name', 150)->nullable();
            $table->string('nominee_relation', 50)->nullable(); // Father, Mother, Spouse, etc.
            $table->integer('nominee_age')->nullable();
            $table->string('appointee_name', 150)->nullable(); // Required if Nominee is a minor (< 18)
            $table->string('appointee_relation', 50)->nullable();

            // Vehicle Identity Numbers (Required to issue policy)
            $table->string('vehicle_chassis', 100)->nullable();
            $table->string('vehicle_engine', 100)->nullable();

            // Previous Policy Info
            $table->string('pre_insurer_name', 150)->nullable();
            $table->string('pre_insurer_no', 100)->nullable();

            // Carrier / Insurer References
            $table->string('insurer_proposal_ref', 100)->nullable(); // e.g. "PR500000719857"
            $table->string('insurer_quote_ref', 100)->nullable();    // e.g. "QT/26/6270406187"

            // Status Tracking
            $table->string('status', 30)->default('pending'); // pending, payment_pending, success, failed

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
