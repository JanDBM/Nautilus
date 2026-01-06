<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('n8n_configs', function (Blueprint $table) {
            $table->string('http_method', 10)->default('POST');
        });
    }

    public function down(): void
    {
        Schema::table('n8n_configs', function (Blueprint $table) {
            $table->dropColumn('http_method');
        });
    }
};
