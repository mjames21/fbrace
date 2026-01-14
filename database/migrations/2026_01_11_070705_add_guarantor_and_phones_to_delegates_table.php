<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delegates', function (Blueprint $table) {
            if (!Schema::hasColumn('delegates', 'phone_primary')) {
                $table->string('phone_primary', 32)->nullable()->after('name');
            }

            if (!Schema::hasColumn('delegates', 'phone_secondary')) {
                $table->string('phone_secondary', 32)->nullable()->after('phone_primary');
            }

            if (!Schema::hasColumn('delegates', 'guarantor_id')) {
                $table->foreignId('guarantor_id')
                    ->nullable()
                    ->constrained('guarantors')
                    ->nullOnDelete()
                    ->after('district_id');
            }

            $table->index(['guarantor_id']);
        });
    }

    public function down(): void
    {
        Schema::table('delegates', function (Blueprint $table) {
            if (Schema::hasColumn('delegates', 'guarantor_id')) {
                $table->dropConstrainedForeignId('guarantor_id');
            }
            if (Schema::hasColumn('delegates', 'phone_secondary')) {
                $table->dropColumn('phone_secondary');
            }
            if (Schema::hasColumn('delegates', 'phone_primary')) {
                $table->dropColumn('phone_primary');
            }
        });
    }
};
