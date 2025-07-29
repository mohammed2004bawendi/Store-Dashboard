<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id',  'total_price', 'status'];

    public function customer(): BelongsTo
    {
    return $this->belongsTo(Customer::class);
    }
    public function products(): BelongsToMany
    {
     return $this->belongsToMany(Product::class)
        ->withPivot('quantity', 'price')
        ->withTimestamps();
    }
}
