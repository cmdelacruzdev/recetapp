<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'nombre', 'pasos', 'imagen', 'casa_id'];

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredient')->withPivot('quantity');
    }
}