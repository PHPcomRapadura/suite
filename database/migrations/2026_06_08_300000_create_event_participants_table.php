<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('registration_order');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('ticket_type');
            $table->decimal('amount', 10, 2);
            $table->datetime('purchased_at');
            $table->string('payment_status', 100);
            $table->boolean('checked_in')->default(false);
            $table->string('discount_coupon', 100)->nullable();
            $table->string('payment_method', 100)->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'registration_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_participants');
    }
};
