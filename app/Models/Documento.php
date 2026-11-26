<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    protected $guarded = [];

    public function documentable() { return $this->morphTo(); }
}
