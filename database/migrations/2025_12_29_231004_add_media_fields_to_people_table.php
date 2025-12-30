<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->boolean('is_journalist')->default(false)->after('notes');
            $table->string('beat')->nullable()->after('is_journalist');
            $table->text('media_notes')->nullable()->after('beat');
            $table->enum('responsiveness', ['high', 'medium', 'low', 'unknown'])->default('unknown')->after('media_notes');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn(['is_journalist', 'beat', 'media_notes', 'responsiveness']);
        });
    }
};
