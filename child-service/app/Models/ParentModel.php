<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentModel extends Model
{
    protected $table = 'parents';

    public function children()
    {
        return $this->hasMany(Child::class);
    }
}
