<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'cost_price', 'price',
        'image', 'barcode','sku', 'description', 'is_active',
    ];
    protected $with = ['category', 'transactionItems'];

    public function category()
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    
    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class);
    }
}
