<?php

namespace Database\Seeders;

use App\Models\N8nConfig;
use Illuminate\Database\Seeder;

class N8nConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a sample n8n configuration for testing
        N8nConfig::create([
            'webhook_url' => 'http://10.1.42.123:5678/webhook/500adb4a-82ba-4fbf-b5f7-c164cf308fbd',
            'api_key' => null,
            'is_active' => true,
        ]);
    }
}