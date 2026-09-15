<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Usuario extends Model
{
    use SoftDeletes;

    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'telefono',
        'domicilio',
        'contrasena'
    ];

    protected $hidden = ['contrasena', 'deleted_at'];

    // Relaciones 

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function consultas()
    {
        return $this->hasMany(Consulta::class, 'usuario_id');
    }

    public function favoritos()
    {
        return $this->belongsToMany(Propiedad::class, 'favoritos', 'usuario_id', 'propiedad_id');
    }

    public function propiedades()
    {
        return $this->hasMany(Propiedad::class, 'usuario_id');
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'usuario_id');
    }

}