<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    protected $table = 'servicios';

    public $timestamps = false;

    protected $fillable = [
        'nombre'
    ];

    public function propiedades()
    {
        return $this->belongsToMany(
            Propiedad::class,
            'propiedad_servicio',   
            'servicio_id',         
            'propiedad_id'          
        );
    }
}