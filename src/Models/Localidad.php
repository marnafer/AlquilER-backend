<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Localidad extends Model
{
    use SoftDeletes;

    protected $table = 'localidades';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = ['nombre', 'codigo_postal', 'provincia_id'];

    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'provincia_id');
    }

    public function propiedades()
    {
        return $this->hasMany(Propiedad::class, 'localidad_id');
    }
}