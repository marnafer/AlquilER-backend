<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Servicio extends Model
{
    use SoftDeletes;

    protected $table = 'servicios';

    protected $fillable = [
        'nombre'
    ];

    public $timestamps = false;

    protected $hidden = ['deleted_at'];

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