<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delegate_candidate_status', function (Blueprint $table) {
            if (!Schema::hasColumn('delegate_candidate_status', 'pending_stance')) {
                $table->string('pending_stance')->nullable()->after('stance');
            }

            if (!Schema::hasColumn('delegate_candidate_status', 'pending_confidence')) {
                $table->unsignedSmallInteger('pending_confidence')->nullable()->after('confidence');
            }

            if (!Schema::hasColumn('delegate_candidate_status', 'pending_reason')) {
                $table->text('pending_reason')->nullable()->after('pending_confidence');
            }

            if (!Schema::hasColumn('delegate_candidate_status', 'pending_by_user_id')) {
                $table->foreignId('pending_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->after('pending_reason');
            }

            if (!Schema::hasColumn('delegate_candidate_status', 'approved_by_user_id')) {
                $table->foreignId('approved_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->after('pending_by_user_id');
            }

            if (!Schema::hasColumn('delegate_candidate_status', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('delegate_candidate_status', function (Blueprint $table) {
            if (Schema::hasColumn('delegate_candidate_status', 'approved_at')) {
                $table->dropColumn('approved_at');
            }

            if (Schema::hasColumn('delegate_candidate_status', 'approved_by_user_id')) {
                $table->dropConstrainedForeignId('approved_by_user_id');
            }

            if (Schema::hasColumn('delegate_candidate_status', 'pending_by_user_id')) {
                $table->dropConstrainedForeignId('pending_by_user_id');
            }

            if (Schema::hasColumn('delegate_candidate_status', 'pending_reason')) {
                $table->dropColumn('pending_reason');
            }

            if (Schema::hasColumn('delegate_candidate_status', 'pending_confidence')) {
                $table->dropColumn('pending_confidence');
            }

            if (Schema::hasColumn('delegate_candidate_status', 'pending_stance')) {
                $table->dropColumn('pending_stance');
            }
        });
    }
};
