<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('AI Chatbot') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-gray-800 dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <!-- Connection Status -->
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <div id="connection-status" class="w-3 h-3 rounded-full bg-yellow-500"></div>
                            <span id="connection-text" class="text-sm text-gray-400">Connecting...</span>
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ route('settings.index') }}" class="text-purple-400 hover:text-purple-300 text-sm">
                                Settings
                            </a>
                            <a href="{{ route('history.index') }}" class="text-purple-400 hover:text-purple-300 text-sm">
                                History
                            </a>
                        </div>
                    </div>

                    <!-- Chat Container -->
                    <div class="bg-gray-900 rounded-lg border border-gray-700">
                        <!-- Messages Area -->
                        <div id="messages-container" class="h-96 overflow-y-auto p-4 space-y-4">
                            <div class="text-center text-gray-500 py-8">
                                <div class="text-4xl mb-2">🤖</div>
                                <p class="text-lg">Welcome to De Boer Marine AI Assistant</p>
                                <p class="text-sm">How can I help you today?</p>
                            </div>
                        </div>

                        <!-- Input Area -->
                        <div class="border-t border-gray-700 p-4">
                            <form id="chat-form" class="flex space-x-2">
                                <textarea
                                    id="message-input"
                                    rows="1"
                                    class="flex-1 bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none"
                                    placeholder="Type your message here..."
                                    maxlength="1000"
                                ></textarea>
                                <button
                                    type="submit"
                                    id="send-button"
                                    class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500"
                                >
                                    Send
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Auto-resize textarea
        const textarea = document.getElementById('message-input');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });

        // Chat functionality
        let currentConversationId = null;
        let isSending = false;

        // Check n8n connection status
        async function checkConnectionStatus() {
            try {
                const response = await fetch('/api/settings/test-webhook', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                const statusDot = document.getElementById('connection-status');
                const statusText = document.getElementById('connection-text');

                if (data.success) {
                    statusDot.className = 'w-3 h-3 rounded-full bg-green-500';
                    statusText.textContent = 'Connected to n8n';
                } else {
                    statusDot.className = 'w-3 h-3 rounded-full bg-red-500';
                    statusText.textContent = 'Not connected to n8n';
                }
            } catch (error) {
                const statusDot = document.getElementById('connection-status');
                const statusText = document.getElementById('connection-text');
                statusDot.className = 'w-3 h-3 rounded-full bg-red-500';
                statusText.textContent = 'Connection error';
            }
        }

        // Send message
        document.getElementById('chat-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (isSending) return;
            
            const messageInput = document.getElementById('message-input');
            const message = messageInput.value.trim();
            
            if (!message) return;
            
            isSending = true;
            const sendButton = document.getElementById('send-button');
            sendButton.disabled = true;
            sendButton.textContent = 'Sending...';
            
            // Add user message to chat
            addMessage(message, 'user');
            messageInput.value = '';
            messageInput.style.height = 'auto';
            
            try {
                const response = await fetch('/api/chat/send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        message: message,
                        conversation_id: currentConversationId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    currentConversationId = data.conversation_id;
                    addMessage(data.response, 'ai');
                } else {
                    addMessage('Error: ' + (data.error || 'Failed to get response'), 'error');
                }
            } catch (error) {
                addMessage('Error: Failed to send message. Please try again.', 'error');
            } finally {
                isSending = false;
                sendButton.disabled = false;
                sendButton.textContent = 'Send';
            }
        });

        // Add message to chat
        function addMessage(content, sender) {
            const messagesContainer = document.getElementById('messages-container');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'flex ' + (sender === 'user' ? 'justify-end' : 'justify-start');
            
            const messageContent = document.createElement('div');
            messageContent.className = 'max-w-xs lg:max-w-md px-4 py-2 rounded-lg ' + 
                (sender === 'user' ? 'bg-purple-600 text-white' : 
                 sender === 'error' ? 'bg-red-600 text-white' : 'bg-gray-700 text-gray-100');
            
            messageContent.textContent = content;
            messageDiv.appendChild(messageContent);
            messagesContainer.appendChild(messageDiv);
            
            // Scroll to bottom
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Initialize
        checkConnectionStatus();
        setInterval(checkConnectionStatus, 30000); // Check every 30 seconds
    </script>
    @endpush
</x-app-layout>