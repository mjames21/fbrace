<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();     // PTO, Club of Like Minds, SUMOKAB, etc.
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('delegate_group', function (Blueprint $table) {
            $table->foreignId('delegate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->primary(['delegate_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delegate_group');
        Schema::dropIfExists('groups');
    }
};