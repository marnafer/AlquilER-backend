<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provincia extends Model
{
    protected $table = 'provincias';

    public $timestamps = false;

    protected $fillable = [
        'nombre'
    ];

    public function localidades()
    {
        return $this->hasMany(Localidad::class, 'provincia_id');
    }
}