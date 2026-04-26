<?php

namespace App\Models;

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Child extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'first_name',
        'last_name',
        'username',
        'password',
        'avatar',
        'birthdate'
    ];

    protected $hidden = [
        'password',
    ];

    // 🔗 Relationships

    public function parent()
    {
        return $this->belongsTo(ParentModel::class);
    }

    // 🔐 Optional: automatically hash password
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = bcrypt($value);
        }
    }
}
