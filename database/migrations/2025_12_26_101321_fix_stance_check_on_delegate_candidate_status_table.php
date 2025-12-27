<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('delegate_candidate_status')) {
            return;
        }

        // Drop old constraint if it exists (safe for repeated runs)
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

        // Add the correct constraint for our app stances
        DB::statement("
            ALTER TABLE delegate_candidate_status
            ADD CONSTRAINT delegate_candidate_status_stance_check
            CHECK (stance IN ('for', 'indicative', 'against'));
        ");

        // If you added pending_stance, keep it consistent too (optional but recommended)
        if (Schema::hasColumn('delegate_candidate_status', 'pending_stance')) {
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
            END$$;
        ");

        if (Schema::hasColumn('delegate_candidate_status', 'pending_stance')) {
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
        }
    }
};
