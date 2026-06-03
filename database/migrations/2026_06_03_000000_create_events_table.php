<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('edition')->nullable();
            $table->text('description')->nullable();
            $table->datetime('starts_at');
            $table->datetime('ends_at')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_online')->default(false);
            $table->enum('status', ['rascunho', 'publicado', 'encerrado', 'cancelado'])->default('rascunho');
            $table->boolean('is_accepting_talks')->default(false);
            $table->unsignedInteger('max_attendees')->nullable();
            $table->string('cover_image', 500)->nullable();
            $table->string('logo', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
