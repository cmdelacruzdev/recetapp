<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class House extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'nombre_casa'];
}