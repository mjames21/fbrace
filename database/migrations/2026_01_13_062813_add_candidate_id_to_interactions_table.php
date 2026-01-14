<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('interactions')) {
            // If you truly don't have interactions table, create it.
            Schema::create('interactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('delegate_id')->constrained()->cascadeOnDelete();
                $table->foreignId('candidate_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type', 50)->default('note');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['delegate_id', 'created_at']);
                $table->index(['candidate_id', 'created_at']);
                $table->index(['type', 'created_at']);
            });

            return;
        }

        // Table exists: add missing columns safely
        Schema::table('interactions', function (Blueprint $table) {
            if (!Schema::hasColumn('interactions', 'candidate_id')) {
                $table->foreignId('candidate_id')->nullable()->after('delegate_id')->constrained()->nullOnDelete();
                $table->index(['candidate_id', 'created_at']);
            }
            if (!Schema::hasColumn('interactions', 'type')) {
                $table->string('type', 50)->default('note')->after('candidate_id');
                $table->index(['type', 'created_at']);
            }
            if (!Schema::hasColumn('interactions', 'notes')) {
                $table->text('notes')->nullable()->after('type');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('interactions')) return;

        Schema::table('interactions', function (Blueprint $table) {
            if (Schema::hasColumn('interactions', 'candidate_id')) {
                $table->dropConstrainedForeignId('candidate_id');
            }
            if (Schema::hasColumn('interactions', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('interactions', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
