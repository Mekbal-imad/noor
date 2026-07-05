<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ParentModel extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'parents';

    protected $fillable = [
        'name', 'email', 'password',
        'phone', 'photo', 'is_active'
    ];

    protected $hidden = ['password', 'remember_token'];

    public function students()
    {
        return $this->hasMany(Student::class, 'parent_id');
    }
}