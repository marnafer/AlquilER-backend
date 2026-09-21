<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Propiedad extends Model
{
    use SoftDeletes;

    protected $table = 'propiedades';

    public $timestamps = false;

    protected $fillable = [
        'titulo',
        'descripcion',
        'precio',
        'expensas',
        'direccion',
        'cantidad_ambientes',
        'cantidad_dormitorios',
        'cantidad_banos',
        'capacidad',
        'disponible',
        'categoria_id',
        'localidad_id',
        'usuario_id'
    ];

    protected $casts = [
        'precio' => 'float',
        'expensas' => 'float',
        'disponible' => 'boolean',
        'cantidad_ambientes' => 'integer',
        'cantidad_dormitorios' => 'integer',
        'cantidad_banos' => 'integer',
        'capacidad' => 'integer',
        'categoria_id' => 'integer',
        'localidad_id' => 'integer',
        'usuario_id' => 'integer'
    ];

    protected $hidden = [
        'deleted_at'
    ];

    protected $appends = [
        'imagen_url'
    ];

    public function categoria()
    {
        return $this->belongsTo(
            Categoria::class,
            'categoria_id'
        );
    }

    public function localidad()
    {
        return $this->belongsTo(
            Localidad::class,
            'localidad_id'
        );
    }

    public function usuario()
    {
        return $this->belongsTo(
            Usuario::class,
            'usuario_id'
        );
    }

    public function imagenes()
    {
        return $this->hasMany(
            PropiedadImagen::class,
            'propiedad_id'
        );
    }

    public function reservas()
    {
        return $this->hasMany(
            Reserva::class,
            'propiedad_id'
        );
    }

    public function servicios()
    {
        return $this->belongsToMany(
            Servicio::class,
            'propiedad_servicio',
            'propiedad_id',
            'servicio_id'
        );
    }

    public function consultas()
    {
        return $this->hasMany(
            Consulta::class,
            'propiedad_id'
        );
    }

    public function favoritos()
    {
        return $this->hasMany(
            Favorito::class,
            'propiedad_id'
        );
    }

    public function imagenPrincipal()
    {
        return $this->hasOne(
            PropiedadImagen::class,
            'propiedad_id'
        )->where('es_principal', 1);
    }

    public function imagenDestacada()
    {
        $principal = $this->imagenPrincipal;

        if ($principal) {
            return $principal;
        }

        return $this->imagenes->first();
    }

    public function getImagenUrlAttribute(): ?string
    {
        $imagen = $this->imagenDestacada();

        return $imagen?->ruta;
    }
}