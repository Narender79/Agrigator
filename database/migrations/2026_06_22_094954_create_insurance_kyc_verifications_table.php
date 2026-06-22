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
        Schema::create('insurance_kyc_verifications', function (Blueprint $table) {
            $table->id(); // PK, auto-increment BigInt

            // Link to the user (unsigned BigInt matching users.id)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

            // Link to the insurance session/unique_id in users_insurance
            $table->string('session_id', 200)->index();

            // Links to Proposal / Quote references
            $table->string('proposal_no', 200)->nullable()->index();
            $table->string('quote_id', 200)->nullable()->index();

            // Identity Check Details
            $table->string('id_type', 20)->default('PAN'); // e.g. PAN, AADHAAR
            $table->string('id_number_masked', 50); // e.g. XXXXX2512P
            $table->string('id_number_hash', 128)->index(); // SHA-256 hash of actual ID for uniqueness checks

            // Name of the customer submitted vs name verification outcome
            $table->string('customer_name', 255);
            $table->boolean('ckyc_success')->default(false);

            // Trace ID returned by the Insurer (e.g. Tata AIG traceId)
            $table->string('trace_id', 250)->nullable();

            // Status tracker
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_kyc_verifications');
    }
};
