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
        Schema::table('make_mapping', function (Blueprint $table) {
            $table->unsignedBigInteger('dr_make_id')->nullable()->change();
            $table->string('vehicle_type', 10)->nullable()->after('status');
            $table->unique(['insurer_id', 'vehicle_type', 'insurer_make_id'], 'uq_insurer_make_mapping');
        });

        Schema::table('model_mapping', function (Blueprint $table) {
            $table->unsignedBigInteger('dr_make_id')->nullable()->change();
            $table->unsignedBigInteger('dr_model_id')->nullable()->change();
            $table->string('insurer_make_id', 50)->nullable()->after('insurer_id');
            $table->string('vehicle_type', 10)->nullable()->after('status');
            $table->unique(['insurer_id', 'vehicle_type', 'insurer_model_id'], 'uq_insurer_model_mapping');
        });

        Schema::table('trim_mapping', function (Blueprint $table) {
            $table->unsignedBigInteger('dr_make_id')->nullable()->change();
            $table->unsignedBigInteger('dr_model_id')->nullable()->change();
            $table->unsignedBigInteger('dr_trim_id')->nullable()->change();
            $table->string('insurer_make_id', 50)->nullable()->after('insurer_id');
            $table->string('insurer_model_id', 50)->nullable()->after('insurer_make_id');
            $table->string('vehicle_type', 10)->nullable()->after('status');
            $table->unique(['insurer_id', 'vehicle_type', 'insurer_trim_id'], 'uq_insurer_trim_mapping');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('make_mapping', function (Blueprint $table) {
            $table->dropUnique('uq_insurer_make_mapping');
            $table->dropColumn('vehicle_type');
            $table->unsignedBigInteger('dr_make_id')->nullable(false)->change();
        });

        Schema::table('model_mapping', function (Blueprint $table) {
            $table->dropUnique('uq_insurer_model_mapping');
            $table->dropColumn(['insurer_make_id', 'vehicle_type']);
            $table->unsignedBigInteger('dr_make_id')->nullable(false)->change();
            $table->unsignedBigInteger('dr_model_id')->nullable(false)->change();
        });

        Schema::table('trim_mapping', function (Blueprint $table) {
            $table->dropUnique('uq_insurer_trim_mapping');
            $table->dropColumn(['insurer_make_id', 'insurer_model_id', 'vehicle_type']);
            $table->unsignedBigInteger('dr_make_id')->nullable(false)->change();
            $table->unsignedBigInteger('dr_model_id')->nullable(false)->change();
            $table->unsignedBigInteger('dr_trim_id')->nullable(false)->change();
        });
    }
};
