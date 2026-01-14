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
        // Regions table
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Countries table
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->char('iso_code', 2)->unique(); // ISO 3166-1 alpha-2
            $table->char('iso_code_3', 3)->nullable(); // ISO 3166-1 alpha-3
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('region_id');
        });

        // US States and Territories table
        Schema::create('us_states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->char('abbreviation', 2)->unique();
            $table->enum('type', ['state', 'territory', 'district'])->default('state');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Polymorphic pivot table for geographic tagging
        Schema::create('geographables', function (Blueprint $table) {
            $table->id();
            $table->morphs('geographable'); // geographable_id, geographable_type (Person, Project, Organization)
            $table->string('geographic_type'); // 'region', 'country', 'us_state'
            $table->unsignedBigInteger('geographic_id');
            $table->timestamps();

            // Indexes
            $table->index(['geographic_type', 'geographic_id']);
            $table->unique(['geographable_id', 'geographable_type', 'geographic_type', 'geographic_id'], 'geographable_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geographables');
        Schema::dropIfExists('us_states');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('regions');
    }
};

