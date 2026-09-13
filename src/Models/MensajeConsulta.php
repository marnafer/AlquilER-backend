<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MensajeConsulta extends Model
{
    use SoftDeletes;

    protected $table = 'mensajes_consultas';

    public $timestamps = false;

    protected $fillable = [
        'consulta_id',
        'usuario_id',
        'mensaje',
        'fecha_mensaje'
    ];

    /**
     * Relación: El mensaje pertenece a una consulta específica.
     */
    public function consulta()
    {
        return $this->belongsTo(Consulta::class, 'consulta_id');
    }

    /**
     * Relación: El mensaje fue escrito por un usuario (dueño o interesado).
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}