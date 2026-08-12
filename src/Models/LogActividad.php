<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogActividad extends Model
{
    protected $table = 'logs_actividad';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'accion',
        'ip_address',
        'fecha'
    ];

    protected $casts = [
        'usuario_id' => 'integer',
        'accion' => 'string',
        'ip_address' => 'string',
        'fecha' => 'datetime'
    ];

    /**
     * Relación con Usuario
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Obtener todos los logs con información de usuario
     */
    public static function getAll()
    {
        return self::with('usuario')
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'usuario_id' => $log->usuario_id,
                    'accion' => $log->accion,
                    'ip_address' => $log->ip_address,
                    'fecha' => $log->fecha,
                    'usuario_nombre' => $log->usuario
                        ? ($log->usuario->nombre . ' ' . $log->usuario->apellido)
                        : null,
                    'usuario_email' => $log->usuario->email ?? null
                ];
            });
    }

    /**
     * Obtener log por ID
     */
    public static function getById($id)
    {
        $log = self::with('usuario')->find($id);

        if (!$log) {
            return null;
        }

        return [
            'id' => $log->id,
            'usuario_id' => $log->usuario_id,
            'accion' => $log->accion,
            'ip_address' => $log->ip_address,
            'fecha' => $log->fecha,
            'usuario_nombre' => $log->usuario
                ? ($log->usuario->nombre . ' ' . $log->usuario->apellido)
                : null,
            'usuario_email' => $log->usuario->email ?? null
        ];
    }

    /**
     * Logs por usuario
     */
    public static function getByUsuario($usuarioId)
    {
        return self::where('usuario_id', $usuarioId)
            ->with('usuario')
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'usuario_id' => $log->usuario_id,
                    'accion' => $log->accion,
                    'ip_address' => $log->ip_address,
                    'fecha' => $log->fecha,
                    'usuario_nombre' => $log->usuario
                        ? ($log->usuario->nombre . ' ' . $log->usuario->apellido)
                        : null
                ];
            });
    }

    /**
     * Logs por rango de fechas
     */
    public static function getByFechaRango($desde, $hasta)
    {
        return self::with('usuario')
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderBy('fecha', 'desc')
            ->get();
    }

    /**
     * Búsqueda por acción
     */
    public static function getByAccion($texto)
    {
        return self::with('usuario')
            ->where('accion', 'LIKE', "%{$texto}%")
            ->orderBy('fecha', 'desc')
            ->get();
    }

    /**
     * Crear log
     */
    public static function createLog(array $data)
    {
        return self::create([
            'usuario_id' => $data['usuario_id'] ?? null,
            'accion' => $data['accion'],
            'ip_address' => $data['ip_address'] ?? null,
            'fecha' => $data['fecha'] ?? date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Registrar acción
     */
    public static function registrar($usuarioId, $accion, $ip = null)
    {
        return self::create([
            'usuario_id' => $usuarioId,
            'accion' => $accion,
            'ip_address' => $ip,
            'fecha' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Eliminar logs antiguos
     */
    public static function deleteOldLogs($dias)
    {
        $fechaLimite = date('Y-m-d H:i:s', strtotime("-{$dias} days"));

        return self::where('fecha', '<', $fechaLimite)->delete();
    }

    /**
     * Eliminar logs por usuario
     */
    public static function deleteByUsuario($usuarioId)
    {
        return self::where('usuario_id', $usuarioId)->delete();
    }

    /**
     * Verificar existencia
     */
    public static function existsLog($id)
    {
        return self::where('id', $id)->exists();
    }

    /**
     * Estadísticas
     */
    public static function getEstadisticas()
    {
        $total = self::count();

        $porDia = self::selectRaw('DATE(fecha) as dia, COUNT(*) as total')
            ->groupBy('dia')
            ->orderBy('dia', 'desc')
            ->limit(7)
            ->get();

        $topUsuarios = self::selectRaw('usuario_id, COUNT(*) as total')
            ->whereNotNull('usuario_id')
            ->groupBy('usuario_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('usuario')
            ->get()
            ->map(function ($log) {
                return [
                    'usuario_id' => $log->usuario_id,
                    'nombre' => $log->usuario->nombre ?? null,
                    'apellido' => $log->usuario->apellido ?? null,
                    'total' => $log->total
                ];
            });

        $acciones = self::selectRaw('accion, COUNT(*) as total')
            ->groupBy('accion')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'total' => $total,
            'por_dia' => $porDia,
            'top_usuarios' => $topUsuarios,
            'acciones' => $acciones
        ];
    }
}