<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('favoriteable');
            $table->timestamps();

            $table->unique(
                ['user_id', 'favoriteable_type', 'favoriteable_id'],
                'favorites_user_favoriteable_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
