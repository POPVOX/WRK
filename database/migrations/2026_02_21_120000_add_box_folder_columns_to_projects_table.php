<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('box_folder_id')->nullable()->unique()->after('parent_project_id');
            $table->string('box_folder_status', 32)->default('pending')->after('box_folder_id');
            $table->text('box_folder_error')->nullable()->after('box_folder_status');
            $table->timestamp('box_folder_synced_at')->nullable()->after('box_folder_error');

            $table->index('box_folder_status');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique('projects_box_folder_id_unique');
            $table->dropIndex(['box_folder_status']);
            $table->dropColumn([
                'box_folder_id',
                'box_folder_status',
                'box_folder_error',
                'box_folder_synced_at',
            ]);
        });
    }
};
