<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('insurer_id')->constrained('insurers')->onDelete('cascade');

            // Reference to the main quote request session
            $table->string('quote_request_session_id', 64)->nullable()->index();

            $table->string('vehicle_id', 50)->nullable(); // Droom vehicle reference
            $table->string('product_id', 50)->nullable(); // Insurer product reference

            $table->string('coverage_type'); // comprehensive, third_party, own_damage
            $table->decimal('idv', 12, 2)->default(0.00);
            $table->string('idv_type')->default('system_calculated'); // system_calculated, user_customised
            $table->integer('policy_term_months')->default(12);

            $table->json('selected_add_ons')->nullable();
            $table->integer('ncb_percentage')->default(0);
            $table->boolean('ncb_verified')->default(false);
            $table->date('previous_policy_expiry')->nullable();
            $table->boolean('is_new_vehicle')->default(false);

            // Premium Details
            $table->decimal('own_damage_premium', 12, 2)->default(0.00);
            $table->decimal('third_party_premium', 12, 2)->default(0.00);
            $table->decimal('add_on_premium', 12, 2)->default(0.00);
            $table->decimal('pa_cover_premium', 12, 2)->default(0.00);
            $table->decimal('ncb_discount', 12, 2)->default(0.00);

            // Deductibles and Discounts
            $table->decimal('voluntary_deductible', 12, 2)->default(0.00);
            $table->decimal('voluntary_deductible_discount', 12, 2)->default(0.00);
            $table->decimal('anti_theft_discount', 12, 2)->default(0.00);
            $table->decimal('aai_discount', 12, 2)->default(0.00);

            // Totals
            $table->decimal('net_premium', 12, 2)->default(0.00);
            $table->decimal('gst', 12, 2)->default(0.00);
            $table->decimal('total_premium', 12, 2)->default(0.00);

            $table->string('status', 30)->default('active'); // active, expired, purchased, abandoned
            $table->timestamp('valid_until')->nullable(); // Quote expiry

            $table->string('insurer_quote_ref')->nullable(); // Reference ID returned by insurer's API
            $table->string('source_channel')->default('web'); // web, mobile, api, agent

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
