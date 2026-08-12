<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'roles';

    public $timestamps = false;

    protected $fillable = [
        'nombre'
    ];

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

    /**
     * Verificar si existe un rol con ese nombre
     */
    public static function existsByNombre(
        $nombre,
        $excluirId = null
    ) {
        $query = self::where(
            'nombre',
            $nombre
        );

        if ($excluirId) {
            $query->where(
                'id',
                '!=',
                $excluirId
            );
        }

        return $query->exists();
    }

    /**
     * Verificar si tiene usuarios asociados
     */
    public function hasUsuarios()
    {
        return $this->usuarios()->count() > 0;
    }

    /**
     * Obtener roles con cantidad de usuarios
     */
    public static function getAllWithCount()
    {
        return self::withCount('usuarios')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($rol) {
                return [
                    'id' => $rol->id,
                    'nombre' => $rol->nombre,
                    'total_usuarios' => $rol->usuarios_count
                ];
            });
    }
}