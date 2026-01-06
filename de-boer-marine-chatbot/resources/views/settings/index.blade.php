<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('n8n Configuration') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-gray-800 dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-100 mb-2">n8n Webhook Configuration</h3>
                        <p class="text-gray-400 text-sm">Configure your n8n webhook URL to connect the chatbot to your workflows.</p>
                    </div>

                    <!-- Configuration Form -->
                    <form id="settings-form" class="space-y-6">
                        <div>
                            <label for="webhook_url" class="block text-sm font-medium text-gray-300 mb-2">
                                Webhook URL
                            </label>
                            <input
                                type="url"
                                id="webhook_url"
                                name="webhook_url"
                                value="{{ $config?->webhook_url ?? '' }}"
                                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="https://your-n8n-instance.com/webhook/..."
                                required
                            >
                            <p class="mt-1 text-sm text-gray-400">
                                Enter your n8n webhook URL. This should be the full URL to your webhook node.
                            </p>
                        </div>

                        <div>
                            <label for="api_key" class="block text-sm font-medium text-gray-300 mb-2">
                                API Key (Optional)
                            </label>
                            <input
                                type="password"
                                id="api_key"
                                name="api_key"
                                value="{{ $config?->api_key ?? '' }}"
                                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                placeholder="Your n8n API key (if required)"
                            >
                            <p class="mt-1 text-sm text-gray-400">
                                Optional API key for authentication with your n8n instance.
                            </p>
                        </div>

                        <div>
                            <label for="http_method" class="block text-sm font-medium text-gray-300 mb-2">
                                HTTP Method
                            </label>
                            <select
                                id="http_method"
                                name="http_method"
                                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            >
                                <option value="POST" {{ ($config?->http_method ?? 'POST') === 'POST' ? 'selected' : '' }}>POST</option>
                                <option value="GET" {{ ($config?->http_method ?? 'POST') === 'GET' ? 'selected' : '' }}>GET</option>
                            </select>
                            <p class="mt-1 text-sm text-gray-400">
                                Choose the method your n8n webhook expects.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="timeout_ms" class="block text-sm font-medium text-gray-300 mb-2">Timeout (ms)</label>
                                <input
                                    type="number"
                                    id="timeout_ms"
                                    name="timeout_ms"
                                    min="1000"
                                    max="60000"
                                    step="500"
                                    value="{{ $config?->timeout_ms ?? 50000 }}"
                                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                >
                                <p class="mt-1 text-sm text-gray-400">How long to wait for the webhook before failing.</p>
                            </div>
                            <div>
                                <label for="retries" class="block text-sm font-medium text-gray-300 mb-2">Retries</label>
                                <input
                                    type="number"
                                    id="retries"
                                    name="retries"
                                    min="0"
                                    max="5"
                                    step="1"
                                    value="{{ $config?->retries ?? 1 }}"
                                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                >
                                <p class="mt-1 text-sm text-gray-400">Number of retry attempts on failure.</p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex space-x-4">
                            <button
                                type="submit"
                                id="save-button"
                                class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500"
                            >
                                Save Configuration
                            </button>
                            <button
                                type="button"
                                id="test-button"
                                class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
                            >
                                Test Connection
                            </button>
                        </div>
                    </form>

                    <!-- Status Messages -->
                    <div id="status-message" class="mt-6 hidden">
                        <div class="p-4 rounded-lg">
                            <p id="status-text"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let isTesting = false;
        let isSaving = false;

        // Show status message
        function showStatus(message, type = 'success') {
            const statusDiv = document.getElementById('status-message');
            const statusText = document.getElementById('status-text');
            
            statusText.textContent = message;
            statusDiv.className = `mt-6 ${type === 'success' ? 'bg-green-900 border border-green-700' : 'bg-red-900 border border-red-700'} p-4 rounded-lg`;
            statusDiv.classList.remove('hidden');
            
            // Hide after 5 seconds
            setTimeout(() => {
                statusDiv.classList.add('hidden');
            }, 5000);
        }

        // Test connection
        document.getElementById('test-button').addEventListener('click', async function() {
            if (isTesting) return;
            
            const webhookUrl = document.getElementById('webhook_url').value.trim();
            const apiKey = document.getElementById('api_key').value.trim();
            
            if (!webhookUrl) {
                showStatus('Please enter a webhook URL first', 'error');
                return;
            }
            
            isTesting = true;
            const testButton = document.getElementById('test-button');
            testButton.disabled = true;
            testButton.textContent = 'Testing...';
            
            try {
                const method = document.getElementById('http_method').value;
                let data;
                if (method === 'GET') {
                    const getUrl = `/api/settings/test-webhook?webhook_url=${encodeURIComponent(webhookUrl)}&api_key=${encodeURIComponent(apiKey)}`;
                    const response = await fetch(getUrl, {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    data = await response.json();
                } else {
                    const response = await fetch('/api/settings/test-webhook', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            webhook_url: webhookUrl,
                            api_key: apiKey
                        })
                    });
                    data = await response.json();
                }

                if (data.success) {
                    showStatus('Connection test successful! Your n8n webhook is working correctly.', 'success');
                } else {
                    showStatus('Connection test failed: ' + (data.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                showStatus('Connection test failed: Network error', 'error');
            } finally {
                isTesting = false;
                testButton.disabled = false;
                testButton.textContent = 'Test Connection';
            }
        });

        // Save configuration
        document.getElementById('settings-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (isSaving) return;
            
            const webhookUrl = document.getElementById('webhook_url').value.trim();
            const apiKey = document.getElementById('api_key').value.trim();
            const httpMethod = document.getElementById('http_method').value;
            const timeoutMs = parseInt(document.getElementById('timeout_ms')?.value || '50000', 10);
            const retries = parseInt(document.getElementById('retries')?.value || '1', 10);
            
            if (!webhookUrl) {
                showStatus('Please enter a webhook URL', 'error');
                return;
            }
            
            isSaving = true;
            const saveButton = document.getElementById('save-button');
            saveButton.disabled = true;
            saveButton.textContent = 'Saving...';
            
            try {
                const response = await fetch('/api/settings/webhook', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        webhook_url: webhookUrl,
                        api_key: apiKey,
                        http_method: httpMethod,
                        timeout_ms: timeoutMs,
                        retries: retries
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showStatus('Configuration saved successfully!', 'success');
                } else {
                    showStatus('Failed to save configuration: ' + (data.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                showStatus('Failed to save configuration: Network error', 'error');
            } finally {
                isSaving = false;
                saveButton.disabled = false;
                saveButton.textContent = 'Save Configuration';
            }
        });
    </script>
    @endpush
</x-app-layout>
