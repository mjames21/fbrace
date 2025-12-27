<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_assessments', function (Blueprint $table) {
            $table->id();

            // Each update is an EVENT (history never overwritten)
            $table->foreignId('delegate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();

            // Enforces "no status change without interaction"
            $table->foreignId('interaction_id')->nullable()->constrained()->nullOnDelete();

            // Color coding per candidate
            $table->enum('stance', ['green', 'yellow', 'red']);
            $table->unsignedTinyInteger('confidence')->default(50); // 0..100
            $table->text('notes')->nullable(); // evidence/why

            // Approval workflow
            $table->enum('status', ['submitted', 'approved', 'rejected'])->default('submitted');
            $table->unsignedTinyInteger('approval_steps_required')->default(1);

            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('submitted_at');

            // Step 1 approver
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // Step 2 approver (for high-value delegates)
            $table->foreignId('second_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('second_approved_at')->nullable();

            // Rejections
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->index(['delegate_id', 'candidate_id', 'status']);
            $table->index(['submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_assessments');
    }
};