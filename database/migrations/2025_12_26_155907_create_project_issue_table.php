<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_issue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'issue_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_issue');
    }
};
