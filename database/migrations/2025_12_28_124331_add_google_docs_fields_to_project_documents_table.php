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
        Schema::table('project_documents', function (Blueprint $table) {
            $table->string('google_doc_id')->nullable()->after('url');
            $table->string('google_doc_type')->nullable()->after('google_doc_id'); // doc, sheet, slide, form
            $table->timestamp('last_synced_at')->nullable()->after('google_doc_type');
            $table->longText('cached_content')->nullable()->after('last_synced_at');
            $table->string('visibility')->default('all')->after('cached_content'); // all, management, admin
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_documents', function (Blueprint $table) {
            $table->dropColumn(['google_doc_id', 'google_doc_type', 'last_synced_at', 'cached_content', 'visibility']);
        });
    }
};
