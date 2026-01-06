<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Conversation History') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-gray-800 dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <!-- Search and Filter Controls -->
                    <div class="mb-6 flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <input
                                type="text"
                                id="search-input"
                                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500"
                                placeholder="Search conversations..."
                            >
                        </div>
                        <div class="flex gap-2">
                            <input
                                type="date"
                                id="start-date"
                                class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                            >
                            <input
                                type="date"
                                id="end-date"
                                class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                            >
                            <button
                                id="search-button"
                                class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200"
                            >
                                Search
                            </button>
                        </div>
                    </div>

                    <!-- Conversations List -->
                    <div id="conversations-container" class="space-y-4">
                        <div class="text-center text-gray-500 py-8">
                            <div class="text-4xl mb-2">📚</div>
                            <p class="text-lg">No conversations found</p>
                            <p class="text-sm">Start chatting to see your conversation history here.</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div id="pagination-container" class="mt-6 flex justify-center">
                        <!-- Pagination will be inserted here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let currentPage = 1;
        let searchTerm = '';
        let startDate = '';
        let endDate = '';

        // Load conversations
        async function loadConversations(page = 1) {
            try {
                const params = new URLSearchParams({
                    page: page,
                    limit: 10,
                    search: searchTerm,
                    start_date: startDate,
                    end_date: endDate
                });

                const response = await fetch(`/api/history/conversations?${params}`);
                const data = await response.json();

                displayConversations(data.conversations);
                displayPagination(data.page, data.last_page);
                currentPage = data.page;
            } catch (error) {
                console.error('Error loading conversations:', error);
                showError('Failed to load conversations');
            }
        }

        // Display conversations
        function displayConversations(conversations) {
            const container = document.getElementById('conversations-container');
            
            if (conversations.length === 0) {
                container.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        <div class="text-4xl mb-2">📚</div>
                        <p class="text-lg">No conversations found</p>
                        <p class="text-sm">${searchTerm ? 'Try adjusting your search terms.' : 'Start chatting to see your conversation history here.'}</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = conversations.map(conversation => `
                <div class="bg-gray-700 rounded-lg p-4 hover:bg-gray-600 transition-colors duration-200 cursor-pointer" onclick="viewConversation(${conversation.id})">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-lg font-medium text-white truncate flex-1">${conversation.title || 'Untitled Conversation'}</h3>
                        <div class="flex space-x-2 ml-4">
                            <button
                                onclick="event.stopPropagation(); viewConversation(${conversation.id})"
                                class="text-purple-400 hover:text-purple-300 text-sm"
                            >
                                View
                            </button>
                            <button
                                onclick="event.stopPropagation(); deleteConversation(${conversation.id})"
                                class="text-red-400 hover:text-red-300 text-sm"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-between items-center text-sm text-gray-400">
                        <span>${conversation.messages_count} messages</span>
                        <span>${new Date(conversation.created_at).toLocaleDateString()}</span>
                    </div>
                </div>
            `).join('');
        }

        // Display pagination
        function displayPagination(currentPage, lastPage) {
            const container = document.getElementById('pagination-container');
            
            if (lastPage <= 1) {
                container.innerHTML = '';
                return;
            }

            let paginationHTML = '<div class="flex space-x-2">';
            
            // Previous button
            if (currentPage > 1) {
                paginationHTML += `
                    <button onclick="loadConversations(${currentPage - 1})" class="px-3 py-2 bg-gray-700 text-white rounded hover:bg-gray-600">
                        Previous
                    </button>
                `;
            }

            // Page numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(lastPage, currentPage + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `
                    <button onclick="loadConversations(${i})" class="px-3 py-2 ${i === currentPage ? 'bg-purple-600' : 'bg-gray-700'} text-white rounded hover:bg-purple-700">
                        ${i}
                    </button>
                `;
            }

            // Next button
            if (currentPage < lastPage) {
                paginationHTML += `
                    <button onclick="loadConversations(${currentPage + 1})" class="px-3 py-2 bg-gray-700 text-white rounded hover:bg-gray-600">
                        Next
                    </button>
                `;
            }

            paginationHTML += '</div>';
            container.innerHTML = paginationHTML;
        }

        // View conversation
        function viewConversation(id) {
            window.location.href = `/chat?conversation=${id}`;
        }

        // Delete conversation
        async function deleteConversation(id) {
            if (!confirm('Are you sure you want to delete this conversation?')) {
                return;
            }

            try {
                const response = await fetch(`/api/history/conversation/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    loadConversations(currentPage);
                } else {
                    showError('Failed to delete conversation');
                }
            } catch (error) {
                console.error('Error deleting conversation:', error);
                showError('Failed to delete conversation');
            }
        }

        // Search functionality
        document.getElementById('search-button').addEventListener('click', function() {
            searchTerm = document.getElementById('search-input').value.trim();
            startDate = document.getElementById('start-date').value;
            endDate = document.getElementById('end-date').value;
            loadConversations(1);
        });

        // Enter key search
        document.getElementById('search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('search-button').click();
            }
        });

        // Show error
        function showError(message) {
            const container = document.getElementById('conversations-container');
            container.innerHTML = `
                <div class="text-center text-red-400 py-8">
                    <p class="text-lg">Error</p>
                    <p class="text-sm">${message}</p>
                </div>
            `;
        }

        // Initialize
        loadConversations(1);
    </script>
    @endpush
</x-app-layout>