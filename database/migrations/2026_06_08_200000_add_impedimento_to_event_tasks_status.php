<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE event_tasks MODIFY COLUMN status ENUM('a_fazer', 'em_andamento', 'em_revisao', 'impedimento', 'concluida') DEFAULT 'a_fazer'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE event_tasks MODIFY COLUMN status ENUM('a_fazer', 'em_andamento', 'em_revisao', 'concluida') DEFAULT 'a_fazer'");
        }
    }
};
