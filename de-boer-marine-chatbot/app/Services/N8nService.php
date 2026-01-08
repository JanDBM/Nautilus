<?php

namespace App\Services;

use App\Models\N8nConfig;
use App\Models\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class N8nService
{
    /**
     * Send message to n8n webhook and get response
     */
    public function sendMessage(string $message, ?string $conversationId = null): array
    {
        $config = N8nConfig::where('is_active', true)->first();
        
        if (!$config) {
            return [
                'success' => false,
                'error' => 'No active n8n configuration found'
            ];
        }

        try {
            // Force 5 minutes timeout
            $timeoutSec = 300;
            $retries = max(0, min(5, (int) ($config->retries ?? 1)));

            $sessionId = session()->getId();
            $payload = [
                'message' => $message,
                'conversation_id' => $conversationId,
                'timestamp' => now()->toIso8601String(),
                'session_id' => $sessionId
            ];

            $headers = [];
            if ($config->api_key) {
                $headers['Authorization'] = 'Bearer ' . $config->api_key;
            }

            $query = [
                'message' => $message,
                'conversation_id' => $conversationId,
                'timestamp' => $payload['timestamp'],
                'session_id' => $sessionId
            ];

            $separator = str_contains($config->webhook_url, '?') ? '&' : '?';
            $postUrl = $config->webhook_url . $separator . http_build_query($query);

            $response = Http::timeout($timeoutSec)
                ->withHeaders($headers)
                ->post($postUrl, $payload);

            // Fallback to production webhook if test returns 404
            if ($response->status() === 404 && str_contains($config->webhook_url, '/webhook-test/')) {
                $productionUrl = str_replace('/webhook-test/', '/webhook/', $config->webhook_url);
                $postUrl = $productionUrl . $separator . http_build_query($query);
                $response = Http::timeout($timeoutSec)
                    ->withHeaders($headers)
                    ->post($postUrl, $payload);
            }

            if ($response->successful()) {
                $responseData = $response->json();
                $text = null;
                $convId = null;
                if (is_array($responseData)) {
                    if (array_is_list($responseData)) {
                        $first = $responseData[0] ?? null;
                        if (is_array($first)) {
                            $text = $first['output'] ?? $first['message'] ?? $first['response'] ?? null;
                            $convId = $first['conversation_id'] ?? null;
                        } elseif (is_string($first)) {
                            $text = $first;
                        }
                    } else {
                        $text = $responseData['output'] ?? $responseData['message'] ?? $responseData['response'] ?? null;
                        $convId = $responseData['conversation_id'] ?? null;
                    }
                } elseif (is_string($responseData)) {
                    $text = $responseData;
                }
                if (!$text) {
                    $body = null;
                    try { $body = $response->body(); } catch (\Throwable $t) { $body = null; }
                    $text = $body ?: 'No response received';
                }
                return [
                    'success' => true,
                    'response' => $text,
                    'conversation_id' => $convId ?? $conversationId
                ];
            }

            $bodySnippet = null;
            try { $bodySnippet = substr($response->body(), 0, 300); } catch (\Throwable $t) {}
            return [
                'success' => false,
                'error' => 'n8n webhook returned error: ' . $response->status() . ($bodySnippet ? (' | body: ' . $bodySnippet) : '')
            ];

        } catch (\Exception $e) {
            Log::error('n8n webhook error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Failed to connect to n8n webhook: ' . $e->getMessage()
            ];
        }
    }


    /**
     * Test n8n webhook connection
     */
    public function testConnection(string $webhookUrl, ?string $apiKey = null): array
    {
        try {
            $timeoutSec = 300;
            $retries = 1;
            $headers = [];
            if ($apiKey) {
                $headers['Authorization'] = 'Bearer ' . $apiKey;
            }

            $sessionId = session()->getId();
            $payload = [
                'message' => 'test_connection',
                'test' => true,
                'timestamp' => now()->toIso8601String(),
                'session_id' => $sessionId
            ];

            $query = [
                'message' => 'test_connection',
                'test' => true,
                'timestamp' => $payload['timestamp'],
                'session_id' => $sessionId
            ];

            $separator = str_contains($webhookUrl, '?') ? '&' : '?';
            $postUrl = $webhookUrl . $separator . http_build_query($query);

            $response = Http::timeout($timeoutSec)
                ->withHeaders($headers)
                ->post($postUrl, $payload);

            if ($response->status() === 404 && str_contains($webhookUrl, '/webhook-test/')) {
                $productionUrl = str_replace('/webhook-test/', '/webhook/', $webhookUrl);
                $postUrl = $productionUrl . $separator . http_build_query($query);
                $response = Http::timeout($timeoutSec)
                    ->withHeaders($headers)
                    ->post($postUrl, $payload);
            }

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connection successful'
                ];
            }
            $bodySnippet = null;
            try { $bodySnippet = substr($response->body(), 0, 300); } catch (\Throwable $t) { $bodySnippet = null; }
            return [
                'success' => false,
                'error' => 'Webhook returned status: ' . $response->status() . ($bodySnippet ? (' | body: ' . $bodySnippet) : '')
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test n8n webhook connection via GET
     */
    public function testConnectionGet(string $webhookUrl, ?string $apiKey = null): array
    {
        try {
            $headers = [];
            if ($apiKey) {
                $headers['Authorization'] = 'Bearer ' . $apiKey;
            }

            $sessionId = session()->getId();
            $separator = str_contains($webhookUrl, '?') ? '&' : '?';
            $getUrl = $webhookUrl . $separator . http_build_query(['session_id' => $sessionId]);

            $response = Http::timeout(300)
                ->withHeaders($headers)
                ->get($getUrl);

            if ($response->status() === 404 && str_contains($webhookUrl, '/webhook-test/')) {
                $productionUrl = str_replace('/webhook-test/', '/webhook/', $webhookUrl);
                $getUrl = $productionUrl . $separator . http_build_query(['session_id' => $sessionId]);
                $response = Http::timeout(300)
                    ->withHeaders($headers)
                    ->get($getUrl);
            }

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connection successful (GET)'
                ];
            }

            return [
                'success' => false,
                'error' => 'Webhook returned status: ' . $response->status()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }
}
