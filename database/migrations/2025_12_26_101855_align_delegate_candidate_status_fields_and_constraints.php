<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('delegate_candidate_status')) {
            return;
        }

        // ---- Add pending/approval fields if missing (safe, idempotent)
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

        // ---- Fix CHECK constraint for stance to match app values
        DB::statement("
            DO $$
            BEGIN
                IF EXISTS (
                    SELECT 1
                    FROM pg_constraint c
                    JOIN pg_class t ON t.oid = c.conrelid
                    WHERE t.relname = 'delegate_candidate_status'
                      AND c.conname = 'delegate_candidate_status_stance_check'
                ) THEN
                    ALTER TABLE delegate_candidate_status
                    DROP CONSTRAINT delegate_candidate_status_stance_check;
                END IF;
            END$$;
        ");

        DB::statement("
            ALTER TABLE delegate_candidate_status
            ADD CONSTRAINT delegate_candidate_status_stance_check
            CHECK (stance IN ('for', 'indicative', 'against'));
        ");

        // ---- Optional: keep pending_stance consistent too
        DB::statement("
            DO $$
            BEGIN
                IF EXISTS (
                    SELECT 1
                    FROM pg_constraint c
                    JOIN pg_class t ON t.oid = c.conrelid
                    WHERE t.relname = 'delegate_candidate_status'
                      AND c.conname = 'delegate_candidate_status_pending_stance_check'
                ) THEN
                    ALTER TABLE delegate_candidate_status
                    DROP CONSTRAINT delegate_candidate_status_pending_stance_check;
                END IF;
            END$$;
        ");

        DB::statement("
            ALTER TABLE delegate_candidate_status
            ADD CONSTRAINT delegate_candidate_status_pending_stance_check
            CHECK (pending_stance IS NULL OR pending_stance IN ('for', 'indicative', 'against'));
        ");
    }

    public function down(): void
    {
        if (!Schema::hasTable('delegate_candidate_status')) {
            return;
        }

        DB::statement("
            DO $$
            BEGIN
                IF EXISTS (
                    SELECT 1
                    FROM pg_constraint c
                    JOIN pg_class t ON t.oid = c.conrelid
                    WHERE t.relname = 'delegate_candidate_status'
                      AND c.conname = 'delegate_candidate_status_stance_check'
                ) THEN
                    ALTER TABLE delegate_candidate_status
                    DROP CONSTRAINT delegate_candidate_status_stance_check;
                END IF;

                IF EXISTS (
                    SELECT 1
                    FROM pg_constraint c
                    JOIN pg_class t ON t.oid = c.conrelid
                    WHERE t.relname = 'delegate_candidate_status'
                      AND c.conname = 'delegate_candidate_status_pending_stance_check'
                ) THEN
                    ALTER TABLE delegate_candidate_status
                    DROP CONSTRAINT delegate_candidate_status_pending_stance_check;
                END IF;
            END$$;
        ");
    }
};