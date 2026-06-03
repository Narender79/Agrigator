<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable strict date validation for this migration session
        DB::statement("SET SESSION sql_mode = ''");
        Schema::table('make', function (Blueprint $table) {
            // Adds 'category_id' right after the primary 'id' column
            $table->unsignedBigInteger('category_id')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable strict date validation for this migration session
        DB::statement("SET SESSION sql_mode = ''");
        Schema::table('make', function (Blueprint $table) {
            $table->dropColumn('category_id');
        });
    }
};
