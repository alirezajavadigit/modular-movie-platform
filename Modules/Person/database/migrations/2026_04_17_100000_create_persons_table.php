<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persons', function (Blueprint $table) {
            $table->id();
            $table->json('first_name');
            $table->json('last_name');
            $table->string('slug')->unique();
            $table->json('biography')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('date_of_death')->nullable();
            $table->json('place_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('known_for_department', 100)->nullable();
            $table->decimal('popularity', 8, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('slug');
            $table->index('is_active');
            $table->index('known_for_department');
            $table->index('popularity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persons');
    }
};
