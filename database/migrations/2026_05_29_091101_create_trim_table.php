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
        Schema::create('trim', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('make_id')->index();
            $table->integer('model_id')->index();
            $table->string('trim_name', 150)->index();
            $table->tinyInteger('upcoming')->default(0);
            $table->enum('status', ['active', 'disabled'])->default('active');
            $table->date('launchDate')->nullable();
            $table->tinyInteger('is_new')->default(0);
            $table->string('engine_emission_norm', 4)->nullable();
            $table->string('country_code', 3)->nullable()->default('IN');
            $table->timestamps();
            $table->softDeletes()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trim');
    }
};
