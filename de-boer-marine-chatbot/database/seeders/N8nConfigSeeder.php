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
            'webhook_url' => 'http://10.1.42.123:5678/webhook-test/d12b6693-f02e-4403-a96c-fcc506136aed',
            'api_key' => null,
            'is_active' => true,
        ]);
    }
}