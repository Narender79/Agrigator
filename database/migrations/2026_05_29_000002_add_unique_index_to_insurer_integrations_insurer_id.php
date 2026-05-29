<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurer_integrations', function (Blueprint $table) {
            $table->unique('insurer_id', 'uq_insurer_integrations_insurer_id');
        });
    }

    public function down(): void
    {
        Schema::table('insurer_integrations', function (Blueprint $table) {
            $table->dropUnique('uq_insurer_integrations_insurer_id');
        });
    }
};