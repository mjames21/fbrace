<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('candidate_alliance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->unique()->constrained()->cascadeOnDelete();

            // 'exclusive' = only ONE active target allowed
            // 'split'     = multiple active targets allowed (sum weights <= max_total_weight_percent)
            $table->string('mode')->default('split');
            $table->unsignedTinyInteger('max_total_weight_percent')->default(100);

            $table->timestamps();

            $table->index(['mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_alliance_policies');
    }
};
