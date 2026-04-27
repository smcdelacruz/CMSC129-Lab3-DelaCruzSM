<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Journal;
use Illuminate\Support\Facades\Http;
use Gemini\Laravel\Facades\Gemini;
use Gemini\Enums\Role;
use Gemini\Data\Content;

class GeminiService {

    // Saves a message to the database
    public function saveMessage($chatId, $role, $content) {
        return Message::create([
            'chat_id' => $chatId,
            'role' => $role,
            'content' => $content
        ]);
    }

    // Generates a response from the Gemini API based on the user's message
    public function generateResponse($userId, $chatId, $userMessage) {

        $this->saveMessage($chatId, 'user', $userMessage);

        // System prompt that instructs the AI to use the API route
        $systemPrompt =
            "You are an empathetic Journal Assistant. \n" .
            "IMPORTANT: You do NOT have the user's journal entries yet.\n" .
            "If the user asks a question that requires reading their journal entries, you MUST reply with this exact phrase and absolutely nothing else:\n" .
            "[CALL_API: /api/users/journals]\n\n" .
            "Once the system provides you with the raw JSON data from that API endpoint, formulate your final answer to the user based on that data.\n" .
            "STRICT RULES: \n" .
            "1. Base your answers ONLY on the API data provided. Do not make assumptions.\n" .
            "2. Suggest what to do with their entries based on app features (favorite, delete).\n" .
            "3. Be empathetic and supportive.\n" .
            "4. Match the user's language (English, Tagalog, Hiligaynon).\n" .
            "5. DO NOT use markdown formatting (no asterisks). Use plain text only.\n";

        // Fetch the chat history
        $chatHistory = Message::where('chat_id', $chatId)->orderBy('created_at', 'asc')->get();

        $formattedHistory = [];
        foreach($chatHistory as $msg) {
            $roleEnum = $msg->role === 'user' ? Role::USER : Role::MODEL;
            $formattedHistory[] = Content::parse(part: $msg->content, role: $roleEnum);
        }

        try {
            $chat = Gemini::generativeModel('gemini-2.5-flash')
                ->withSystemInstruction(Content::parse($systemPrompt))
                ->startChat(history: $formattedHistory);

            // Sends the user's question to the AI
            $response = $chat->sendMessage($userMessage);
            $aiReply = $response->text();

            // Check if the AI's reply contains the trigger phrase to call the API
            if (str_contains($aiReply, '[CALL_API: /api/users/journals]')) {

                // API call: simulate the AI calling the API by making an internal request to the Laravel endpoint
                $entries = Journal::where('user_id', $userId)->latest()->take(20)->get();
                $journalJsonData = $entries->toJson();

                // Sends the API JSON data back to Gemini
                $secondPrompt = "Here is the JSON data from the API endpoint:\n" . $journalJsonData . "\n\nPlease provide your final answer to the user now.";
                $response = $chat->sendMessage($secondPrompt);

                // AI reply after receiving the API data
                $aiReply = $response->text();
            }

            // Save and return the final response
            $this->saveMessage($chatId, 'model', $aiReply);
            return $aiReply;

        } catch (\Throwable $e) {
            return "I'm having trouble connecting right now. Error: " . $e->getMessage();
        }
    }
}
