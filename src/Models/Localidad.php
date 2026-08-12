<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Localidad extends Model
{
    protected $table = 'localidades';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = ['nombre', 'codigo_postal', 'provincia_id'];

    public function provincia()
    {
        return $this->belongsTo(Provincia::class); 
    }
}