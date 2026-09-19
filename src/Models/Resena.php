<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Resena extends Model
{
    use SoftDeletes;

    protected $table = 'resenas';

    public $timestamps = false;

    protected $fillable = [
    'reserva_id',
    'tipo',
    'calificador_id',
    'calificacion',
    'comentario',
    'fecha_publicacion',
    ];

    protected $casts = [
        'id' => 'integer',
        'reserva_id' => 'integer',
        'calificador_id' => 'integer',
        'calificacion' => 'integer',
        'fecha_publicacion' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = ['deleted_at'];

    /**
     * Reserva asociada a la reseña.
     */
    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function calificador()
    {
        return $this->belongsTo(Usuario::class, 'calificador_id');
    }
}