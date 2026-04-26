<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Message Model
 *
 * Represents individual messages in a chat conversation.
 * Each message has a role (user or model) and content.
 */
class Message extends Model
{
    protected $fillable = [
        'chat_id',
        'role',
        'content',
    ];
}
