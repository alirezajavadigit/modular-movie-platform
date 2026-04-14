<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')
                ->constrained('movies')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('season_number');
            $table->unsignedSmallInteger('episode_number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('poster')->nullable();
            $table->string('trailer_url')->nullable();
            $table->json('download_links')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['movie_id', 'season_number', 'episode_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
