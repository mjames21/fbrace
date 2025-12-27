<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alliances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('from_candidate_id')
                ->constrained('candidates')
                ->cascadeOnDelete();

            $table->foreignId('to_candidate_id')
                ->constrained('candidates')
                ->cascadeOnDelete();

            // 0.00 - 1.00 (we store percent as 0.25 etc)
            $table->decimal('weight', 6, 4)->default(0.2500);

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['from_candidate_id', 'to_candidate_id']);
            $table->index(['to_candidate_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alliances');
    }
};
