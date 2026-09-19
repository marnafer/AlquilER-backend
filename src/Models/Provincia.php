<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provincia extends Model
{
    use SoftDeletes;

    protected $table = 'provincias';

    public $timestamps = false;

    protected $fillable = [
        'nombre'
    ];

    protected $hidden = ['deleted_at'];

    public function localidades()
    {
        return $this->hasMany(Localidad::class, 'provincia_id');
    }
}