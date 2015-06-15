<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    public function scopeTokenIs($query,$token){
        return $query->where('token','=',$token);
    }
}
