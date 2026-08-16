<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_logs', function (Blueprint $table) {
            $table->decimal('similarity', 10, 8)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('chatbot_logs')
            ->whereNull('similarity')
            ->update(['similarity' => 0]);

        Schema::table('chatbot_logs', function (Blueprint $table) {
            $table->decimal('similarity', 10, 8)->nullable(false)->change();
        });
    }
};
