<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('n8n_configs', function (Blueprint $table) {
            $table->integer('timeout_ms')->default(5000);
            $table->integer('retries')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('n8n_configs', function (Blueprint $table) {
            $table->dropColumn('timeout_ms');
            $table->dropColumn('retries');
        });
    }
};
