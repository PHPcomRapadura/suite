<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_schedule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('talk_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 255)->nullable();
            $table->string('speaker_name', 255)->nullable();
            $table->dateTime('starts_at');
            $table->smallInteger('duration')->unsigned()->default(50);
            $table->string('room', 100)->nullable();
            $table->enum('type', ['palestra', 'intervalo', 'abertura', 'encerramento', 'outro'])->default('palestra');
            $table->tinyInteger('sort_order')->unsigned()->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_schedule_items');
    }
};
