<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categoria extends Model
{
    use SoftDeletes;

    protected $table = 'categorias';
    public $timestamps = false;

    protected $fillable = ['nombre'];

    /**
     * Relación: Una categoría tiene muchas propiedades.
     */
    public function propiedades()
    {
        return $this->hasMany(Propiedad::class, 'categoria_id');
    }
    
}