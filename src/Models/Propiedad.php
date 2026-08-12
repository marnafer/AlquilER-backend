<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Para usar el deleted_at

class Propiedad extends Model
{
    use SoftDeletes; // Habilita el borrado logico de la BD

    // 1. Nombre de la tabla (si no es el plural en ingl�s, hay que aclararlo)
    protected $table = 'propiedades';

    // 2. Campos que permitimoss que se llenen (Mass Assignment)
    // Esto es por seguridad, para que el sanitizador y el modelo trabajen juntos
    protected $fillable = [
    'titulo', 'descripcion', 'precio', 'expensas', 'direccion', 
    'cantidad_ambientes', 'cantidad_dormitorios', 'cantidad_banos', 
    'capacidad', 'disponible', 'categoria_id', 'localidad_id',
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

    // 3. Desactivamos los timestamps autom�ticos si no ten�s 'created_at' y 'updated_at'
    public $timestamps = false; 
    
    // Indicamos que use deleted_at para el Soft Delete
    protected $dates = ['deleted_at'];

    // Relaciones con otros modelos 

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id'); 
    }

    public function localidad()
    {
        return $this->belongsTo(Localidad::class, 'localidad_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id'); 
    }

    public function imagenes()
    {
        return $this->hasMany(PropiedadImagen::class, 'propiedad_id');
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'propiedad_id');
    }

    public function servicios()
    {
        return $this->belongsToMany(Servicio::class, 'propiedad_servicio', 'propiedad_id', 'servicio_id');
    }

    public function consultas()
    {
        return $this->hasMany(Consulta::class, 'propiedad_id');
    }

    public function favoritos()
    {
        return $this->hasMany(Favorito::class, 'propiedad_id');
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
        // 1. Intenta obtener la marcada como principal
        $principal = $this->imagenPrincipal; // Relación 'imagenPrincipal' definida
        if ($principal) {
            return $principal;
        }

        // 2. Si no hay principal, toma la primera de la lista de todas las imágenes
        return $this->imagenes()->first(); 
    }
   
}