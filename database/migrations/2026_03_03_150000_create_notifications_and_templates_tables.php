<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index('read_at');
            });
        }

        if (! Schema::hasTable('notification_templates')) {
            Schema::create('notification_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('kind', 64)->default('manual_notice');
                $table->string('category', 32)->default('general');
                $table->string('title_template');
                $table->text('body_template');
                $table->string('default_level', 24)->default('info');
                $table->string('default_action_label')->nullable();
                $table->string('default_action_url')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['is_active', 'kind']);
                $table->index('category');
            });

            DB::table('notification_templates')->insert([
                [
                    'name' => 'Added To Project',
                    'kind' => 'project_added',
                    'category' => 'project',
                    'title_template' => '{actor_name} added you to a new project',
                    'body_template' => '{project_name}',
                    'default_level' => 'info',
                    'default_action_label' => 'Open Project',
                    'default_action_url' => '/projects/{project_id}',
                    'is_active' => true,
                    'created_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Project Document Uploaded',
                    'kind' => 'project_document_uploaded',
                    'category' => 'project',
                    'title_template' => '{actor_name} uploaded a document to {project_name}',
                    'body_template' => '{document_title}',
                    'default_level' => 'info',
                    'default_action_label' => 'View Document',
                    'default_action_url' => '/projects/{project_id}',
                    'is_active' => true,
                    'created_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Added To Trip',
                    'kind' => 'trip_added',
                    'category' => 'travel',
                    'title_template' => '{actor_name} added you to a new trip',
                    'body_template' => '{trip_name}',
                    'default_level' => 'info',
                    'default_action_label' => 'Open Trip',
                    'default_action_url' => '/travel/{trip_id}',
                    'is_active' => true,
                    'created_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Trip Upcoming',
                    'kind' => 'trip_upcoming',
                    'category' => 'travel',
                    'title_template' => 'You have a trip coming up in {days} days',
                    'body_template' => '{trip_name} starts on {start_date}',
                    'default_level' => 'info',
                    'default_action_label' => 'Open Trip',
                    'default_action_url' => '/travel/{trip_id}',
                    'is_active' => true,
                    'created_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Project Milestone Recorded',
                    'kind' => 'project_milestone_recorded',
                    'category' => 'project',
                    'title_template' => 'Milestone recorded for {project_name}',
                    'body_template' => '{milestone_title}',
                    'default_level' => 'info',
                    'default_action_label' => 'Open Project',
                    'default_action_url' => '/projects/{project_id}',
                    'is_active' => true,
                    'created_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Mentioned In Win',
                    'kind' => 'win_mentioned',
                    'category' => 'team',
                    'title_template' => '{actor_name} mentioned you in a win',
                    'body_template' => '{win_title}',
                    'default_level' => 'success',
                    'default_action_label' => 'View Wins',
                    'default_action_url' => '/accomplishments',
                    'is_active' => true,
                    'created_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('notifications');
    }
};

