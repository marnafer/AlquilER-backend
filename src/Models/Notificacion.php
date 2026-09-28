<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notificacion extends Model
{
    use SoftDeletes;

    protected $table = 'notificaciones';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'tipo',
        'titulo',
        'mensaje',
        'referencia_id',
        'leida',
        'fecha_notificacion'
    ];

    protected $casts = [
        'leida' => 'boolean',
        'fecha_notificacion' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $hidden = ['deleted_at'];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function usuario()
    {
        return $this->belongsTo(
            Usuario::class,
            'usuario_id'
        );
    }
}