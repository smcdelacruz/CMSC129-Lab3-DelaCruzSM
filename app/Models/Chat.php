<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Chat Model to store chat sessions between the user and the AI assistant.
 * Each chat can have multiple messages (not implemented here, but can be added later).
 * For simplicity, we are only storing the chat session metadata (like title and user association).
 */
class Chat extends Model
{
    protected $fillable = [
        'user_id',
        'title',
    ];
}
