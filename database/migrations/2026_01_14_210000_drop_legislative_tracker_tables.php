<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('requirement_reminders');
        Schema::dropIfExists('reporting_requirements');
        Schema::dropIfExists('legislative_reports');
    }

    public function down(): void
    {
        // Tables were removed - no rollback
    }
};

