<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_social_assets', function (Blueprint $table) {
            $table->string('type', 30)->default('announcement')->after('event_id');
            $table->foreignId('talk_id')->nullable()->after('type')->constrained()->cascadeOnDelete();
            $table->foreignId('sponsor_id')->nullable()->after('talk_id')->constrained('event_sponsors')->cascadeOnDelete();
            $table->string('subject_key', 60)->default('event')->after('sponsor_id');

            // A nova unique precisa existir antes de derrubar a antiga: o InnoDB
            // exige que event_id sempre esteja coberto por algum índice (é FK).
            $table->unique(['event_id', 'type', 'format', 'subject_key']);
        });

        Schema::table('event_social_assets', function (Blueprint $table) {
            $table->dropUnique(['event_id', 'format']);
        });
    }

    public function down(): void
    {
        Schema::table('event_social_assets', function (Blueprint $table) {
            $table->unique(['event_id', 'format']);
        });

        Schema::table('event_social_assets', function (Blueprint $table) {
            $table->dropUnique(['event_id', 'type', 'format', 'subject_key']);
            $table->dropForeign(['talk_id']);
            $table->dropForeign(['sponsor_id']);
            $table->dropColumn(['type', 'talk_id', 'sponsor_id', 'subject_key']);
        });
    }
};
