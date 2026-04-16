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
            $table->string('name');
            $table->string('gender');
            $table->float('gender_probability', 2);
            $table->bigInteger('sample_size')->unsigned();
            $table->integer('age');
            $table->enum('age_group', ['child', 'teenager', 'adult', 'senior'])->default('child');
            $table->string('country_id');
            $table->float('country_probability', 2);
            $table->timestamps();
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
