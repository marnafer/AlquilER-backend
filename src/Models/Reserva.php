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
        'estado',
        'fecha_inicio_alquiler',
        'fecha_fin_alquiler',
        'fecha_confirmacion'
    ];

    protected $casts = [
        'fecha_reserva' => 'datetime',
        'fecha_confirmacion' => 'datetime',
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

    public function resenas()
    {
        return $this->hasMany(
            Resena::class,
            'reserva_id'
        );
    }
}