<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaqQa extends Model
{
    protected $table = 'faq_qa';

    protected $fillable = ['intent','question','answer','is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}