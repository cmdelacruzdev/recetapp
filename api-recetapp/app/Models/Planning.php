<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Planning extends Model
{
    protected $fillable = ['casa_id', 'day', 'meal', 'recipe_id'];
}
