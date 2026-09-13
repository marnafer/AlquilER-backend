<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consulta extends Model
{
    use SoftDeletes;

    protected $table = 'consultas';

    protected $fillable = [
        'propiedad_id',
        'usuario_id',
        'fecha_consulta'
    ];

    protected $dates = [
        'fecha_consulta',
        'deleted_at'
    ];

    public $timestamps = false;

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

    /**
     * Relación: Una consulta tiene muchos mensajes en su hilo de conversación.
     */
    public function mensajes()
    {
        return $this->hasMany(MensajeConsulta::class, 'consulta_id');
    }
}