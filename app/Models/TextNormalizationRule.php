<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TextNormalizationRule extends Model
{
    protected $table = 'text_normalization_rules';

    protected $fillable = ['rule_type','pattern','replacement','priority','is_active'];

    protected $casts = [
        'priority'  => 'integer',
        'is_active' => 'boolean',
    ];
}
