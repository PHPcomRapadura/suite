<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_site_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('is_published')->default(false);
            $table->tinyInteger('layout')->unsigned()->default(1);
            $table->string('primary_color', 7)->default('#025c98');
            $table->string('secondary_color', 7)->default('#f59e0b');
            $table->string('font', 50)->default('lexend');
            $table->string('hero_tagline', 255)->nullable();
            $table->string('ticket_url', 500)->nullable();
            $table->longText('code_of_conduct')->nullable();
            $table->json('faq')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_site_configs');
    }
};
