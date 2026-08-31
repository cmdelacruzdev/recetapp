<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = ['username', 'nombre', 'password', 'foto', 'casa_id', 'role', 'status'];
    protected $hidden = ['password', 'remember_token'];

    public function house()
    {
        return $this->belongsTo(House::class, 'casa_id');
    }
}