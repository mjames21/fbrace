<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('delegates', function (Blueprint $table) {
            $table->id();

            $table->string('full_name');

            // Mirrors the document "sections": District Executives, MPs, Councillors, Organizations, etc.
            $table->string('category')->nullable();

            // District -> Region derived via districts.region_id
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();

            $table->string('constituency')->nullable();

            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // Drives 2-step approvals for important delegates
            $table->boolean('is_high_value')->default(false);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['district_id']);
            $table->index(['category']);
            $table->index(['is_high_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delegates');
    }
};