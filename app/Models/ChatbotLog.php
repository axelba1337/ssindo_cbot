<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotLog extends Model
{
    protected $table = 'chatbot_logs';
    public $timestamps = false; // hanya created_at dari DB default

    protected $fillable = [
        'session_id','user_message','bot_answer','source','similarity','top_matches','created_at',
    ];

    protected $casts = [
        'similarity'  => 'decimal:4', // NUMERIC(5,4)
        'top_matches' => 'array',
        'created_at'  => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(ChatbotSession::class, 'session_id', 'id');
    }
}