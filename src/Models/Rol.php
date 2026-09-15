<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rol extends Model
{

    use SoftDeletes;

    protected $table = 'roles';

    public $timestamps = false;

    protected $fillable = [
        'nombre'
    ];

    protected $hidden = ['deleted_at'];

    /**
     * Relación con usuarios
     */
    public function usuarios()
    {
        return $this->hasMany(
            Usuario::class,
            'rol_id'
        );
    }

}