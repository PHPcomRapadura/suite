<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->enum('category', [
                'alimentacao', 'transporte', 'hospedagem', 'equipamentos',
                'marketing', 'infraestrutura', 'palestrantes', 'premiacao', 'outros',
            ]);
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->boolean('is_paid')->default(false);
            $table->string('receipt_url', 500)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_expenses');
    }
};
