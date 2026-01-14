<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id','sku','name','description','unit','stock','reorder_level','price',
    ];

    protected $casts = [
        'stock' => 'integer',
        'reorder_level' => 'integer',
        'price' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }
}