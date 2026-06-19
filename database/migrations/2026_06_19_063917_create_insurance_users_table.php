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
        Schema::create('users_insurance', function (Blueprint $table) {
            $table->id(); // int(10) unsigned auto_increment primary key
            $table->string('insurance_unique_id', 200)->unique()->nullable();
            $table->string('user_id', 20)->nullable();
            $table->string('email', 200)->nullable();
            $table->string('name', 200)->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('vehicleType', 15)->nullable();
            $table->string('make', 200)->nullable();
            $table->string('model', 200)->nullable();
            $table->string('trim', 200)->nullable();
            $table->string('year', 10)->nullable();
            $table->string('quote_id', 200)->nullable();
            $table->string('quote_no', 200)->nullable();
            $table->string('product_id', 100)->nullable();
            $table->string('product_group_id', 100)->nullable();
            $table->string('proposal_id', 200)->nullable();
            $table->string('proposal_no', 200)->nullable();
            $table->string('document_id', 200)->nullable();
            $table->string('nstp_id', 200)->nullable();
            $table->string('payment_id', 200)->nullable();
            $table->string('payment_status', 200)->nullable();
            $table->string('insurance_payment_id', 200)->nullable();
            $table->string('insuranc_payment_ref_no', 200)->nullable();
            $table->string('droom_payment_id', 200)->nullable();
            $table->string('gateway_txn_id', 200)->nullable();
            $table->dateTime('insurance_txn_start_time')->nullable();
            $table->string('pol_plan_id', 100)->nullable();
            $table->string('pol_plan_variant', 150)->nullable();
            $table->string('policy_id', 200)->nullable();
            $table->date('policy_start_date')->nullable();
            $table->string('policy_no', 300)->nullable();
            $table->decimal('net_premium', 12, 2)->nullable();
            // $table->integer('request_no')->nullable();
            $table->text('policy_url')->nullable();
            $table->string('city', 300)->nullable();
            $table->string('registration_no', 15)->nullable();
            $table->string('platform', 255)->default('droom');
            $table->string('status', 255)->default('quoteCreate');
            $table->string('source', 255)->nullable();
            // $table->string('utm_source', 255)->nullable();
            $table->string('quotes_mail', 255)->default('0');
            $table->string('proposal_mail', 255)->default('0');
            $table->string('aggregator_name', 255)->nullable();
            $table->timestamps(); // creates created_at and updated_at

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_insurance');
    }
};
