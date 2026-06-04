<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id')->unique();
            $table->json('vehicle_data'); // Stores normalized Droom Make, Model, Trim, Year
            $table->string('status', 20)->default('pending'); // pending, complete, timeout
            $table->integer('insurer_count')->default(0); // Number of active insurers queried
            $table->timestamp('expires_at')->nullable(); // TTL expiry time for data retention
            $table->timestamps();

            // Indexes for fast polling lookups
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};
