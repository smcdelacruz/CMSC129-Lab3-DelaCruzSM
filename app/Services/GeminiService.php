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

        // Fetch the user's recent journal entries to provide context for the Gemini API
        $entries = Journal::where('user_id', $userId)->latest()->take(10)->get();

        // Prepare the journal entries context for the Gemini API
        $entriesContext = "Here are your recent journal entries:\n";
        if ($entries->isEmpty()) {
            $entriesContext = "You have no recent journal entries.\n";

        } else {
            foreach($entries as $entry) {
                $entriesContext .= "Date: {$entry->created_at->format('M d, Y')}\n";
                $entriesContext .= "Title: {$entry->title}\n";
                $entriesContext .= "Mood: {$entry->mood}\n";
                $entriesContext .= "Content: {$entry->content}\n\n";
            }
        }
        $entriesContext .= "======================================================\n";

        // System prompt for the Gemini API
        $systemPrompt =
            "You are a helpful and empathetic assistant designed to provide support and guidance based on the user's recent journal entries. \n" .
            "Use the following journal entries to understand the user's current emotional state and provide thoughtful responses.\n" .
            "Your goal is to help the user sort their journal entries through the date, mood, and content. \n" .
            "STRICT RULES: \n" .
            "1. Your answers will be based ONLY on the journal entries provided by the user. Do not make assumptions or provide information that is not present in the entries.\n" .
            "2. Suggest what to do with their journal entries based on the application's features. For example, suggest that they can mark an entry as a favorite if it holds special meaning, or suggest that they can delete an entry if it no longer serves them.\n" .
            "3. Always be empathetic and supportive in your responses, acknowledging the user's feelings and experiences as expressed in their journal entries.\n" .
            "4. You are allowed to speak in English, Tagalog, Hiligaynon, and/or a mix of these languages (Taglish). If the user has journal entries in a specific language, respond in that language to create a more personalized experience.\n" .
            "5. DO NOT use markdown formatting.  Do not use asterisks (*), bold (**), or any special formatting. Use plain text only. Remove the asterisks from the journal entries when presenting them in your responses.\n\n" .
            $entriesContext;

        // Fetch the chat history for the Gemini API
        $chatHistory = Message::where('chat_id', $chatId)->orderBy('created_at', 'asc')->get();


        $formattedHistory = [];         // Format the chat history for the Gemini API

        foreach($chatHistory as $msg) {
            $roleEnum = $msg->role === 'user' ? Role::USER : Role::MODEL;

            $formattedHistory[] = Content::parse(part: $msg->content, role: $roleEnum);
        }

        try {
            // Initialize the Gemini chat with the system prompt and chat history
            $chat = Gemini::generativeModel('gemini-2.5-flash')
                ->withSystemInstruction(Content::parse($systemPrompt)) // Inject the engineered prompt
                ->startChat(history: $formattedHistory); // Pass in the formatted history

            $response = $chat->sendMessage($userMessage);

            $aiReply = $response->text();
            $this->saveMessage($chatId, 'model', $aiReply);

            return $aiReply;

        } catch (\Throwable $e) {
            return "I'm having trouble connecting right now. Error: " . $e->getMessage();
        }

    }

}
