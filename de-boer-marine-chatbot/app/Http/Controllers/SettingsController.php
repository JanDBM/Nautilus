<?php

namespace App\Http\Controllers;

use App\Models\N8nConfig;
use App\Services\N8nService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    protected $n8nService;

    public function __construct(N8nService $n8nService)
    {
        $this->n8nService = $n8nService;
    }

    /**
     * Display the settings page
     */
    public function index()
    {
        $config = N8nConfig::where('is_active', true)->orderByDesc('id')->first() ?? N8nConfig::first();
        return view('settings.index', compact('config'));
    }

    /**
     * Update n8n webhook configuration
     */
    public function updateWebhook(Request $request)
    {
        $request->validate([
            'webhook_url' => 'required|url|max:500',
            'api_key' => 'nullable|string|max:255',
            'http_method' => 'nullable|in:GET,POST'
            , 'timeout_ms' => 'nullable|integer|min:1000|max:60000'
            , 'retries' => 'nullable|integer|min:0|max:5'
        ]);

        $config = N8nConfig::query()->firstOrCreate([]);

        $config->fill([
            'webhook_url' => $request->input('webhook_url'),
            'api_key' => $request->input('api_key'),
            'is_active' => true,
            'http_method' => 'POST',
            'timeout_ms' => $request->input('timeout_ms') ?: ($config->timeout_ms ?? 50000),
            'retries' => $request->input('retries') ?: ($config->retries ?? 1),
        ])->save();

        N8nConfig::where('id', '!=', $config->id)->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'n8n configuration updated successfully'
        ]);
    }

    /**
     * Test n8n webhook connection
     */
    public function testWebhook(Request $request)
    {
        $request->validate([
            'webhook_url' => 'nullable|url',
            'api_key' => 'nullable|string'
        ]);

        $webhookUrl = $request->input('webhook_url');
        $apiKey = $request->input('api_key');

        if (!$webhookUrl) {
            $config = N8nConfig::where('is_active', true)->orderByDesc('id')->first() ?? N8nConfig::first();
            if (!$config || !$config->webhook_url) {
                return response()->json([
                    'success' => false,
                    'error' => 'No n8n webhook configured yet'
                ], 400);
            }
            $webhookUrl = $config->webhook_url;
            $apiKey = $apiKey ?? $config->api_key;
        }

        $result = $this->n8nService->testConnection($webhookUrl, $apiKey);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message']
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error']
        ], 400);
    }

    /**
     * Test n8n webhook connection via GET
     */
    public function testWebhookGet(Request $request)
    {
        $request->validate([
            'webhook_url' => 'nullable|url',
            'api_key' => 'nullable|string'
        ]);

        $webhookUrl = $request->query('webhook_url');
        $apiKey = $request->query('api_key');

        if (!$webhookUrl) {
            $config = N8nConfig::where('is_active', true)->orderByDesc('id')->first() ?? N8nConfig::first();
            if (!$config || !$config->webhook_url) {
                return response()->json([
                    'success' => false,
                    'error' => 'No n8n webhook configured yet'
                ], 400);
            }
            $webhookUrl = $config->webhook_url;
            $apiKey = $apiKey ?? $config->api_key;
        }

        $result = $this->n8nService->testConnectionGet($webhookUrl, $apiKey);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message']
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error']
        ], 400);
    }
}
