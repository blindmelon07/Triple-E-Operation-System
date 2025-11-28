<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = ['customer_id', 'date', 'total'];
    /** @use HasFactory<\Database\Factories\SaleFactory> */
    use HasFactory;
}
