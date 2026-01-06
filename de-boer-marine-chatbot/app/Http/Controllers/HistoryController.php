<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HistoryController extends Controller
{
    /**
     * Display the conversation history page
     */
    public function index()
    {
        return view('history.index');
    }

    /**
     * Get paginated conversation history
     */
    public function getConversations(Request $request)
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
        $search = $request->input('search', '');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Conversation::where('user_id', $user->id)
            ->withCount('messages')
            ->orderBy('updated_at', 'desc');

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhereHas('messages', function ($mq) use ($search) {
                      $mq->where('content', 'like', '%' . $search . '%');
                  });
            });
        }

        // Apply date range filter
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $conversations = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'conversations' => $conversations->items(),
            'total' => $conversations->total(),
            'page' => $conversations->currentPage(),
            'last_page' => $conversations->lastPage()
        ]);
    }

    /**
     * Delete a conversation
     */
    public function deleteConversation($id)
    {
        $user = Auth::user();
        
        $conversation = Conversation::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'error' => 'Conversation not found'
            ], 404);
        }

        $conversation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversation deleted successfully'
        ]);
    }
}
