<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropiedadImagen extends Model
{
    protected $table = 'propiedad_imagenes';

    public $timestamps = false;

    protected $fillable = [
        'propiedad_id',
        'ruta',
        'descripcion',
        'es_principal'
    ];

    /**
     * Relación: Una imagen pertenece a una propiedad.
     */
    public function propiedad()
    {
        return $this->belongsTo(Propiedad::class, 'propiedad_id');
    }
}