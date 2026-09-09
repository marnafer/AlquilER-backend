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
        'tipo',
        'calificado_id',
        'calificador_id',
        'calificacion',
        'comentario',
        'fecha_publicacion'
    ];

    protected $casts = [
        'id' => 'int',
        'reserva_id' => 'int',
        'calificado_id' => 'int',
        'calificador_id' => 'int',
        'calificacion' => 'int',
        'fecha_publicacion' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Relación con Reserva
     */
    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    /**
     * Relación con el usuario calificado
     * (inquilino o propietario según el tipo)
     */
    public function calificado()
    {
        return $this->belongsTo(Usuario::class, 'calificado_id');
    }

    /**
     * Relación con el usuario que califica
     */
    public function calificador()
    {
        return $this->belongsTo(Usuario::class, 'calificador_id');
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopeDePropiedad($query)
    {
        return $query->where('tipo', 'propiedad');
    }

    public function scopeDeInquilino($query)
    {
        return $query->where('tipo', 'inquilino');
    }

    /**
     * Scope para filtrar por calificación
     */
    public function scopePorCalificacion($query, int $calificacion)
    {
        return $query->where('calificacion', $calificacion);
    }

    public function scopePositivas($query)
    {
        return $query->where('calificacion', '>=', 4);
    }

    public function scopeNegativas($query)
    {
        return $query->where('calificacion', '<=', 2);
    }

    /**
     * Scope para filtrar por propiedad (a través de reserva)
     */
    public function scopePorPropiedad($query, int $propiedadId)
    {
        return $query->whereHas('reserva', function ($q) use ($propiedadId) {
            $q->where('propiedad_id', $propiedadId);
        });
    }

    /**
     * Scope para filtrar por usuario (como inquilino en la reserva)
     */
    public function scopePorUsuario($query, int $usuarioId)
    {
        return $query->whereHas('reserva', function ($q) use ($usuarioId) {
            $q->where('usuario_id', $usuarioId);
        });
    }

    /**
     * Obtener promedio de calificación de una propiedad
     */
    public static function getPromedioByPropiedad($propiedadId)
    {
        $result = self::dePropiedad()
            ->porPropiedad($propiedadId)
            ->selectRaw('AVG(calificacion) as promedio, COUNT(*) as total')
            ->first();

        return [
            'promedio' => round($result->promedio ?? 0, 1),
            'total' => (int)($result->total ?? 0)
        ];
    }

    /**
     * Obtener promedio de calificación de un usuario (como inquilino)
     */
    public static function getPromedioByUsuario($usuarioId)
    {
        $result = self::deInquilino()
            ->where('calificado_id', $usuarioId)
            ->selectRaw('AVG(calificacion) as promedio, COUNT(*) as total')
            ->first();

        return [
            'promedio' => round($result->promedio ?? 0, 1),
            'total' => (int)($result->total ?? 0)
        ];
    }

    /**
     * Verificar si una reserva ya tiene reseña de un tipo específico
     */
    public static function existePorReservaYTipo($reservaId, $tipo)
    {
        return self::where('reserva_id', $reservaId)
            ->where('tipo', $tipo)
            ->exists();
    }

    /**
     * Verificar si existe una reseña para una reserva (cualquier tipo)
     */
    public static function existePorReserva($reservaId)
    {
        return self::where('reserva_id', $reservaId)->exists();
    }

    /**
     * Verificar que la reserva exista y esté finalizada
     */
    public static function reservaExistsAndFinalizada($reservaId)
    {
        return Reserva::where('id', $reservaId)
            ->where('estado', 'finalizada')
            ->whereNull('deleted_at')
            ->exists();
    }

    /**
     * Verificar si el usuario es dueño de la reseña (calificador)
     */
    public static function perteneceAUsuario($resenaId, $usuarioId)
    {
        return self::where('id', $resenaId)
            ->where('calificador_id', $usuarioId)
            ->exists();
    }

    /**
     * Verificar si el usuario es el calificado en la reseña
     */
    public static function perteneceACalificado($resenaId, $usuarioId)
    {
        return self::where('id', $resenaId)
            ->where('calificado_id', $usuarioId)
            ->exists();
    }

    /**
     * Estadísticas generales
     */
    public static function getEstadisticas()
    {
        $total = self::count();

        $promedioGeneral = self::avg('calificacion');

        $distribucion = self::selectRaw('calificacion, COUNT(*) as cantidad')
            ->groupBy('calificacion')
            ->orderBy('calificacion', 'desc')
            ->get()
            ->toArray();

        $porTipo = self::selectRaw('tipo, COUNT(*) as cantidad')
            ->groupBy('tipo')
            ->get()
            ->toArray();

        return [
            'total' => $total,
            'promedio_general' => round($promedioGeneral ?? 0, 1),
            'distribucion' => $distribucion,
            'por_tipo' => $porTipo
        ];
    }
}