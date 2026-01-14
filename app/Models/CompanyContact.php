<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyContact extends Model
{
    protected $fillable = ['type','value','is_primary'];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function scopePrimary($query, ?string $type = null)
    {
        if ($type) $query->where('type', $type);
        return $query->where('is_primary', true);
    }
}