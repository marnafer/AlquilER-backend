<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropiedadImagen extends Model
{
    protected $table = 'propiedad_imagenes';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'propiedad_id',
        'ruta',
        'descripcion',
        'es_principal'
    ];

    public function propiedad()
    {
        return $this->belongsTo(
            Propiedad::class,
            'propiedad_id'
        );
    }
}