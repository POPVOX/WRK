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
        Schema::table('users', function (Blueprint $table) {
            $table->string('title')->nullable()->after('name');
            $table->string('role')->default('staff')->after('title'); // staff, contractor, intern, fellow
            $table->string('access_level')->default('staff')->after('role'); // staff, management, admin
            $table->date('start_date')->nullable()->after('access_level');
            $table->date('end_date')->nullable()->after('start_date');
            $table->foreignId('reports_to')->nullable()->after('end_date')->constrained('users')->nullOnDelete();
            $table->text('responsibilities')->nullable()->after('reports_to');
            $table->text('bio')->nullable()->after('responsibilities');
            $table->string('phone')->nullable()->after('bio');
            $table->string('linkedin')->nullable()->after('phone');
            $table->json('onboarding_checklist')->nullable()->after('linkedin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reports_to');
            $table->dropColumn([
                'title',
                'role',
                'access_level',
                'start_date',
                'end_date',
                'responsibilities',
                'bio',
                'phone',
                'linkedin',
                'onboarding_checklist',
            ]);
        });
    }
};
