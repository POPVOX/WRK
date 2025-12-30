<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Funder fields
            $table->boolean('is_funder')->default(false)->after('description');
            $table->text('funder_priorities')->nullable()->after('is_funder');
            $table->text('funder_preferences')->nullable()->after('funder_priorities');

            // Congressional fields
            $table->boolean('is_congressional')->default(false)->after('funder_preferences');
            $table->string('bioguide_id')->nullable()->after('is_congressional');
            $table->string('chamber')->nullable()->after('bioguide_id'); // house, senate, joint
            $table->string('state', 2)->nullable()->after('chamber');
            $table->string('district', 10)->nullable()->after('state');
            $table->string('party')->nullable()->after('district');
            $table->json('committees')->nullable()->after('party');
            $table->json('leadership_positions')->nullable()->after('committees');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'is_funder',
                'funder_priorities',
                'funder_preferences',
                'is_congressional',
                'bioguide_id',
                'chamber',
                'state',
                'district',
                'party',
                'committees',
                'leadership_positions'
            ]);
        });
    }
};
