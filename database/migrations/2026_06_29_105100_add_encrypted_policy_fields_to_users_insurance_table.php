<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users_insurance', function (Blueprint $table) {
            $table->string('encrypted_policy_id')->nullable()->after('policy_id');
            $table->string('encrypted_policy_no')->nullable()->after('policy_no');
        });
    }

    public function down(): void
    {
        Schema::table('users_insurance', function (Blueprint $table) {
            $table->dropColumn(['encrypted_policy_id', 'encrypted_policy_no']);
        });
    }
};
