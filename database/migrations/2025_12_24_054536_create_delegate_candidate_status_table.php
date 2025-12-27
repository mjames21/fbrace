<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('delegate_candidate_status', function (Blueprint $table) {
            $table->id();

            // Snapshot: latest APPROVED state per Delegate×Candidate
            $table->foreignId('delegate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();

            $table->enum('stance', ['green', 'yellow', 'red'])->default('yellow');
            $table->unsignedTinyInteger('confidence')->default(50);

            // Freshness
            $table->timestamp('last_confirmed_at')->nullable();

            // Link back to audit event
            $table->foreignId('last_assessment_id')
                ->nullable()
                ->constrained('support_assessments')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['delegate_id', 'candidate_id']);
            $table->index(['candidate_id', 'stance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delegate_candidate_status');
    }
};