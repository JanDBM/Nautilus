<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\N8nService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    protected $n8nService;

    public function __construct(N8nService $n8nService)
    {
        $this->n8nService = $n8nService;
    }

    /**
     * Display the chat interface
     */
    public function index()
    {
        return view('chat.index');
    }

    /**
     * Send message to n8n and get response
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'conversation_id' => 'nullable|exists:conversations,id'
        ]);

        $user = Auth::user();
        $message = $request->input('message');
        $conversationId = $request->input('conversation_id');

        // Get or create conversation
        $conversation = null;
        if ($conversationId) {
            $conversation = Conversation::where('id', $conversationId)
                ->where(function ($query) use ($user) {
                    $query->where('user_id', $user?->id)->orWhereNull('user_id');
                })
                ->first();
        }

        if (!$conversation) {
            $conversation = new Conversation();
            $conversation->user_id = $user?->id;
            $conversation->title = substr($message, 0, 50) . (strlen($message) > 50 ? '...' : '');
            $conversation->save();
        }

        // Save user message
        $userMessage = new Message();
        $userMessage->conversation_id = $conversation->id;
        $userMessage->sender_type = 'user';
        $userMessage->content = $message;
        $userMessage->save();

        // Send to n8n and get response
        $result = $this->n8nService->sendMessage($message, $conversation->id);

        if ($result['success']) {
            // Save AI response
            $aiMessage = new Message();
            $aiMessage->conversation_id = $conversation->id;
            $aiMessage->sender_type = 'ai';
            $aiMessage->content = $result['response'];
            $aiMessage->save();

            return response()->json([
                'success' => true,
                'response' => $result['response'],
                'conversation_id' => $conversation->id,
                'message_id' => $aiMessage->id
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error']
        ], 500);
    }

    /**
     * Get conversation history
     */
    public function getHistory(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'conversations' => [],
                'total' => 0,
                'page' => 1
            ]);
        }

        $page = $request->input('page', 1);
        $limit = $request->input('limit', 20);

        $conversations = Conversation::where('user_id', $user->id)
            ->with(['messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }])
            ->orderBy('updated_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'conversations' => $conversations->items(),
            'total' => $conversations->total(),
            'page' => $conversations->currentPage()
        ]);
    }

    /**
     * Get single conversation with messages
     */
    public function getConversation($id)
    {
        $user = Auth::user();
        
        $conversation = Conversation::where('id', $id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user?->id)->orWhereNull('user_id');
            })
            ->with(['messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }])
            ->first();

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'error' => 'Conversation not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'conversation' => $conversation
        ]);
    }
}
