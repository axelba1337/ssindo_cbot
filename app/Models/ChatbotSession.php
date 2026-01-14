<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotSession extends Model
{
    protected $table = 'chatbot_sessions';
    public $timestamps = false; // pakai started_at default NOW()

    protected $fillable = ['user_identifier','started_at','meta'];

    protected $casts = [
        'meta' => 'array',
        'started_at' => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(ChatbotLog::class, 'session_id', 'id');
    }
}
