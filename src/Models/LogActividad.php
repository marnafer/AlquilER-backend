<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogActividad extends Model
{
    protected $table = 'logs_actividad';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'accion',
        'ip_address',
        'fecha'
    ];

    protected $casts = [
        'usuario_id' => 'integer',
        'accion' => 'string',
        'ip_address' => 'string',
        'fecha' => 'datetime'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
