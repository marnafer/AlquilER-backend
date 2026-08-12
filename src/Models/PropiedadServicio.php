<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropiedadServicio extends Model
{
    protected $table = 'propiedad_servicio';

    public $timestamps = false;

    protected $fillable = [
        'propiedad_id',
        'servicio_id'
    ];

    /*
    |--------------------------------------
    | RELACIONES
    |--------------------------------------
    */

    /**
     * Una relación pertenece a una propiedad
     */
    public function propiedad()
    {
        return $this->belongsTo(Propiedad::class, 'propiedad_id');
    }

    /**
     * Una relación pertenece a un servicio
     */
    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }
}