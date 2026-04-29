<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('persons')->cascadeOnDelete();
            $table->morphs('creditable');
            $table->string('role', 50);
            $table->string('character_name')->nullable();
            $table->string('credited_as')->nullable();
            $table->string('department', 100)->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['creditable_id', 'creditable_type', 'role'], 'credits_creditable_role_idx');
            $table->index(['person_id', 'role']);
            $table->index('department');
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};
