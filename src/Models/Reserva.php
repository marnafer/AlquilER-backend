<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reserva extends Model
{
    use SoftDeletes;

    protected $table = 'reservas';

    public $timestamps = false;

    protected $fillable = [
        'propiedad_id',
        'usuario_id',
        'fecha_inicio_alquiler',
        'fecha_fin_alquiler',
        'estado'
    ];

    protected $casts = [
        'fecha_inicio_alquiler' => 'date',
        'fecha_fin_alquiler' => 'date',
        'fecha_reserva' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function propiedad()
    {
        return $this->belongsTo(
            Propiedad::class,
            'propiedad_id'
        );
    }

    public function usuario()
    {
        return $this->belongsTo(
            Usuario::class,
            'usuario_id'
        );
    }

    public function resena()
    {
        return $this->hasOne(
            Resena::class,
            'reserva_id'
        );
    }
}