<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rol extends Model
{

    public const USUARIO = 1;
    public const ADMIN = 2;
    
    use SoftDeletes;

    protected $table = 'roles';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = ['nombre'];

    protected $hidden = ['deleted_at'];

    public function usuarios()
    {
        return $this->hasMany(
            Usuario::class,
            'rol_id'
        );
    }
}