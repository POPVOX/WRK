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
        Schema::create('country_travel_advisories', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2)->unique(); // ISO code
            $table->string('country_name');
            $table->enum('advisory_level', ['1', '2', '3', '4']);
            $table->string('advisory_title'); // "Exercise Normal Precautions", etc.
            $table->boolean('is_prohibited')->default(false);
            $table->text('advisory_summary')->nullable();
            $table->string('state_dept_url')->nullable();
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('country_travel_advisories');
    }
};
