<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotKnowledge extends Model
{
    protected $table = 'chatbot_knowledge';

    public const CREATED_AT = null;
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'kind','title','content_text','embedding','source_table','source_id','updated_at',
    ];

    protected $casts = [
        'embedding' => 'array',
        'source_id' => 'integer',
        'updated_at' => 'datetime',
    ];
}