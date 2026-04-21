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
        Schema::create('profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('gender')->index();
            $table->float('gender_probability', 2)->index();
            $table->integer('age')->index();
            $table->enum('age_group', ['child', 'teenager', 'adult', 'senior'])->index()->default('child');
            $table->string('country_id', 2)->index();
            $table->string('country_name')->index()->nullable();
            $table->float('country_probability', 2)->index();
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
