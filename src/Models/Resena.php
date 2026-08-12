<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Resena extends Model
{
    use SoftDeletes;

    protected $table = 'resenas';

    public $timestamps = false;

    protected $fillable = [
        'reserva_id',
        'calificacion',
        'comentario',
        'fecha_publicacion'
    ];

    protected $casts = [
        'calificacion' => 'integer',
        'fecha_publicacion' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Relación con Reserva
     */
    public function reserva()
    {
        return $this->belongsTo(
            Reserva::class,
            'reserva_id'
        );
    }

    /**
     * Obtener todas las reseñas
     */
    public static function getAll()
    {
        return self::with([
                'reserva.propiedad',
                'reserva.usuario'
            ])
            ->orderBy('fecha_publicacion', 'desc')
            ->get()
            ->map(function ($resena) {

                return [
                    'id' => $resena->id,
                    'reserva_id' => $resena->reserva_id,
                    'calificacion' => $resena->calificacion,
                    'comentario' => $resena->comentario,
                    'fecha_publicacion' => $resena->fecha_publicacion,

                    'usuario_nombre' =>
                        $resena->reserva->usuario
                            ? $resena->reserva->usuario->nombre . ' ' .
                              $resena->reserva->usuario->apellido
                            : null,

                    'propiedad_titulo' =>
                        $resena->reserva->propiedad->titulo ?? null
                ];
            });
    }

    /**
     * Obtener una reseña por ID
     */
    public static function getById($id)
    {
        $resena = self::with([
                'reserva.propiedad',
                'reserva.usuario'
            ])
            ->find($id);

        if (!$resena) {
            return null;
        }

        return [
            'id' => $resena->id,
            'reserva_id' => $resena->reserva_id,
            'calificacion' => $resena->calificacion,
            'comentario' => $resena->comentario,
            'fecha_publicacion' => $resena->fecha_publicacion,

            'usuario_nombre' =>
                $resena->reserva->usuario
                    ? $resena->reserva->usuario->nombre . ' ' .
                      $resena->reserva->usuario->apellido
                    : null,

            'usuario_email' =>
                $resena->reserva->usuario->email ?? null,

            'propiedad_titulo' =>
                $resena->reserva->propiedad->titulo ?? null,

            'propiedad_id' =>
                $resena->reserva->propiedad_id ?? null
        ];
    }

    /**
     * Obtener reseñas por propiedad
     */
    public static function getByPropiedad($propiedadId)
    {
        return self::whereHas(
                'reserva',
                function ($query) use ($propiedadId) {
                    $query->where(
                        'propiedad_id',
                        $propiedadId
                    );
                }
            )
            ->with([
                'reserva.usuario'
            ])
            ->orderBy(
                'fecha_publicacion',
                'desc'
            )
            ->get()
            ->map(function ($resena) {

                return [
                    'id' => $resena->id,
                    'calificacion' => $resena->calificacion,
                    'comentario' => $resena->comentario,
                    'fecha_publicacion' => $resena->fecha_publicacion,

                    'usuario_nombre' =>
                        $resena->reserva->usuario
                            ? $resena->reserva->usuario->nombre . ' ' .
                              $resena->reserva->usuario->apellido
                            : null
                ];
            });
    }

    /**
     * Obtener reseñas por usuario
     */
    public static function getByUsuario($usuarioId)
    {
        return self::whereHas(
                'reserva',
                function ($query) use ($usuarioId) {
                    $query->where(
                        'usuario_id',
                        $usuarioId
                    );
                }
            )
            ->with([
                'reserva.propiedad'
            ])
            ->orderBy(
                'fecha_publicacion',
                'desc'
            )
            ->get()
            ->map(function ($resena) {

                return [
                    'id' => $resena->id,
                    'calificacion' => $resena->calificacion,
                    'comentario' => $resena->comentario,
                    'fecha_publicacion' => $resena->fecha_publicacion,

                    'propiedad_titulo' =>
                        $resena->reserva->propiedad->titulo ?? null
                ];
            });
    }

    /**
     * Obtener promedio de una propiedad
     */
    public static function getPromedioByPropiedad($propiedadId)
    {
        $result = self::whereHas(
                'reserva',
                function ($query) use ($propiedadId) {
                    $query->where(
                        'propiedad_id',
                        $propiedadId
                    );
                }
            )
            ->selectRaw(
                'AVG(calificacion) as promedio, COUNT(*) as total'
            )
            ->first();

        return [
            'promedio' =>
                round(
                    $result->promedio ?? 0,
                    1
                ),

            'total' =>
                (int) ($result->total ?? 0)
        ];
    }

    /**
     * Crear reseña
     */
    public static function createResena($data)
    {
        return self::create($data);
    }

    /**
     * Actualizar reseña
     */
    public static function updateResena(
        $id,
        $data
    ) {
        $resena = self::find($id);

        if (!$resena) {
            return false;
        }

        return $resena->update($data);
    }

    /**
     * Eliminar reseña
     */
    public static function deleteResena($id)
    {
        $resena = self::find($id);

        if (!$resena) {
            return false;
        }

        return $resena->delete();
    }

    /**
     * Verificar existencia
     */
    public static function exists($id)
    {
        return self::where(
            'id',
            $id
        )->exists();
    }

    /**
     * Verificar si existe una reseña
     * para una reserva
     */
    public static function existePorReserva(
        $reservaId
    ) {
        return self::where(
            'reserva_id',
            $reservaId
        )->exists();
    }

    /**
     * Verificar que la reserva exista
     * y esté finalizada
     */
    public static function reservaExistsAndFinalizada(
        $reservaId
    ) {
        return Reserva::where(
                'id',
                $reservaId
            )
            ->where(
                'estado',
                'finalizada'
            )
            ->whereNull(
                'deleted_at'
            )
            ->exists();
    }

    /**
     * Estadísticas generales
     */
    public static function getEstadisticas()
    {
        $total = self::count();

        $promedioGeneral =
            self::avg('calificacion');

        $distribucion =
            self::selectRaw(
                'calificacion, COUNT(*) as cantidad'
            )
            ->groupBy('calificacion')
            ->orderBy(
                'calificacion',
                'desc'
            )
            ->get()
            ->toArray();

        return [
            'total' => $total,

            'promedio_general' =>
                round(
                    $promedioGeneral ?? 0,
                    1
                ),

            'distribucion' =>
                $distribucion
        ];
    }

    /**
     * Obtener reseña con su reserva
     */
    public static function getWithReserva($id)
    {
        return self::with('reserva')
            ->find($id);
    }

    /**
     * Verificar si el usuario es dueño de la reseña
     */
    public static function perteneceAUsuario(
        $resenaId,
        $usuarioId
    ) {
        return self::where('id', $resenaId)
            ->whereHas(
                'reserva',
                function ($query) use ($usuarioId) {
                    $query->where(
                        'usuario_id',
                        $usuarioId
                    );
                }
            )
            ->exists();
    }
}