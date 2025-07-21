<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class CancelReason extends Model
{
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
