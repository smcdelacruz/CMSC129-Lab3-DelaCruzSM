<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatBotController extends Controller
{
    protected $geminiService;

    // Inject the service we just made
    public function __construct(GeminiService $geminiService) {
        $this->geminiService = $geminiService;
    }
    // This method will handle incoming chat message from the frontend widget
    public function chat(Request $request) {
        $request->validate([
            'message' => 'required|string',
            'chat_id' => 'nullable|exists:chats,id' // It might be a brand new chat
        ]);

        $chatId = $request->chat_id;
        $userId = Auth::id();

        if (!$userId) {
            return response()->json([
                'reply' => 'User not authenticated',
                'chat_id' => null
            ]);
        }

        // If there is no active chat, create one for the logged-in user
        if (!$chatId) {
            $chat = Chat::create([
                'user_id' => $userId, // Assumes the user is logged in to the journal app
                'title' => 'Journal Assistant Chat'
            ]);
            $chatId = $chat->id;
        }

        // Calls the service to get the AI's response
        $reply = $this->geminiService->generateResponse($userId, $chatId, $request->message);

        // Returns the data back to the floating widget
        return response()->json([
            'reply' => $reply,
            'chat_id' => $chatId
        ]);
    }
}
