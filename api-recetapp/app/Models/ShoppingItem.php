<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ShoppingItem extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'casa_id', 'text', 'checked'];
}