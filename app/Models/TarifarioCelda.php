<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarifarioCelda extends Model
{
    protected $guarded = [];

    public function tarifario() { return $this->belongsTo(Tarifario::class); }
    public function plan() { return $this->belongsTo(Plan::class); }
}
