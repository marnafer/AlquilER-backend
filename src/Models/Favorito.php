<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorito extends Model
{
    protected $table = 'favoritos';

    protected $primaryKey = 'id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'propiedad_id'
    ];

    public function usuario()
    {
        return $this->belongsTo(
            Usuario::class,
            'usuario_id'
        );
    }

    public function propiedad()
    {
        return $this->belongsTo(
            Propiedad::class,
            'propiedad_id'
        );
    }
}