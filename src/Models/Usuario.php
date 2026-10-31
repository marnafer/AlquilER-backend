<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Usuario extends Model
{
    use SoftDeletes;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'telefono',
        'domicilio',
        'contrasena',
        'email_verified_at',
        'email_verification_token',
        'email_verification_expires_at'
    ];

    protected $hidden = [
        'contrasena',
        'deleted_at',
        'email_verification_token'
    ];

    protected $casts = [
        'rol_id' => 'integer',
    ];

    public $timestamps = false;

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function rol()
    {
        return $this->belongsTo(
            Rol::class,
            'rol_id'
        );
    }

    public function consultas()
    {
        return $this->hasMany(
            Consulta::class,
            'usuario_id'
        );
    }

    public function favoritos()
    {
        return $this->belongsToMany(
            Propiedad::class,
            'favoritos',
            'usuario_id',
            'propiedad_id'
        );
    }

    public function propiedades()
    {
        return $this->hasMany(
            Propiedad::class,
            'usuario_id'
        );
    }

    public function reservas()
    {
        return $this->hasMany(
            Reserva::class,
            'usuario_id'
        );
    }

    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }
}

