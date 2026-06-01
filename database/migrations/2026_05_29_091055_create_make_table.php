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
        Schema::create('make', function (Blueprint $table) {
            $table->increments('id');
            $table->string('make_org_name', 50)->index();
            $table->string('make_name', 50)->index();
            $table->integer('year_of_founding')->nullable();
            $table->string('ev_offerings', 255)->nullable();
            $table->string('url_india', 255)->nullable();
            $table->timestamps();
            $table->softDeletes()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('make');
    }
};
