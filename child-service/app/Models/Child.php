<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Child extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'parent_id',
        'first_name',
        'last_name',
        'username',
        'password',
        'avatar',
        'subscription_id',
        'age',
        'birthdate',
        'skill_level',
        'status',
        'last_login_at',
        'credentials_rotated_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'last_login_at' => 'datetime',
            'credentials_rotated_at' => 'datetime',
        ];
    }

    public function setPasswordAttribute($value): void
    {
        if ($value !== null && $value !== '') {
            $this->attributes['password'] = bcrypt($value);
        }
    }
}
