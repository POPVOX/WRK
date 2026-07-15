<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('congressional_import_runs', function (Blueprint $table) {
            $table->id();
            $table->text('source');
            $table->unsignedSmallInteger('schema_version')->nullable();
            $table->string('status', 24)->default('running');
            $table->unsignedInteger('observations_processed')->default(0);
            $table->unsignedInteger('observations_created')->default(0);
            $table->unsignedInteger('observations_updated')->default(0);
            $table->json('manifest')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'started_at']);
        });

        Schema::create('congressional_offices', function (Blueprint $table) {
            $table->id();
            $table->char('office_key', 64)->unique();
            $table->string('chamber', 12)->index();
            $table->string('name');
            $table->string('normalized_name');
            $table->string('office_code')->nullable()->index();
            $table->string('office_type', 80)->nullable()->index();
            $table->boolean('is_active')->default(false)->index();
            $table->date('first_seen_at')->nullable();
            $table->date('last_seen_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['chamber', 'normalized_name']);
        });

        Schema::create('congressional_staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->char('profile_key', 64)->unique();
            $table->string('chamber', 12)->index();
            $table->string('display_name');
            $table->string('normalized_name');
            $table->string('identity_hint')->index();
            $table->string('status', 32)->default('reported')->index();
            $table->string('review_status', 32)->default('provisional')->index();
            $table->date('first_seen_at')->nullable();
            $table->date('last_seen_at')->nullable();
            $table->date('latest_period_end')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['chamber', 'normalized_name']);
            $table->index(['person_id', 'status']);
        });

        Schema::create('congressional_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('congressional_staff_profiles')->cascadeOnDelete();
            $table->foreignId('office_id')->constrained('congressional_offices')->cascadeOnDelete();
            $table->char('position_key', 64)->unique();
            $table->string('title');
            $table->string('normalized_title');
            $table->date('first_reported_start')->nullable();
            $table->date('last_reported_end')->nullable();
            $table->boolean('is_current')->default(false)->index();
            $table->string('confidence', 24)->default('reported');
            $table->timestamps();

            $table->index(['profile_id', 'is_current']);
            $table->index(['office_id', 'is_current']);
        });

        Schema::create('congressional_staff_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_run_id')->nullable()->constrained('congressional_import_runs')->nullOnDelete();
            $table->foreignId('profile_id')->constrained('congressional_staff_profiles')->cascadeOnDelete();
            $table->foreignId('office_id')->constrained('congressional_offices')->cascadeOnDelete();
            $table->foreignId('position_id')->constrained('congressional_positions')->cascadeOnDelete();
            $table->string('observation_id', 80)->unique();
            $table->char('source_record_hash', 64)->index();
            $table->string('chamber', 12)->index();
            $table->string('name_raw');
            $table->string('identity_hint')->index();
            $table->string('office_raw');
            $table->string('office_code')->nullable();
            $table->string('office_type', 80)->nullable();
            $table->string('title_raw');
            $table->string('period_label')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable()->index();
            $table->boolean('active_in_latest_report')->default(false)->index();
            $table->json('source_data');
            $table->json('evidence')->nullable();
            $table->timestamps();

            $table->index(['profile_id', 'period_end']);
            $table->index(['office_id', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('congressional_staff_observations');
        Schema::dropIfExists('congressional_positions');
        Schema::dropIfExists('congressional_staff_profiles');
        Schema::dropIfExists('congressional_offices');
        Schema::dropIfExists('congressional_import_runs');
    }
};
