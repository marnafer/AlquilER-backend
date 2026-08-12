<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
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