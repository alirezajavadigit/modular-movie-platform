<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('poster')->nullable();
            $table->string('trailer_url')->nullable();
            $table->json('download_links')->nullable();
            $table->unsignedSmallInteger('release_year');
            $table->string('country')->nullable();
            $table->string('language')->nullable();
            $table->decimal('imdb_score', 3, 1)->nullable();
            $table->string('badge');
            $table->string('type');
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('badge');
            $table->index('release_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
