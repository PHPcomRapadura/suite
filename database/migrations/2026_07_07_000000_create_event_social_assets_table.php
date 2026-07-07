<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_social_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('format', 20);
            $table->string('url', 500);
            $table->string('path', 500);
            $table->timestamps();

            $table->unique(['event_id', 'format']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_social_assets');
    }
};
