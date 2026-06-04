<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('speaker_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('abstract');
            $table->enum('duration', ['25', '50']);
            $table->enum('level', ['iniciante', 'intermediario', 'avancado']);
            $table->enum('status', ['submetida', 'em_analise', 'aprovada', 'rejeitada', 'cancelada'])
                  ->default('submetida');
            $table->text('feedback')->nullable();
            $table->datetime('submitted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talks');
    }
};
