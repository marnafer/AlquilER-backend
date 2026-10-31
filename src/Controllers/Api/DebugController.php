<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Http\Request;
use App\Http\Response;
use App\Security\Authenticator;
use App\Services\PropiedadService;

class DebugController
{
    public function __construct(
        private readonly ?PropiedadService $propiedadService = null,
        private readonly ?Authenticator $auth = null
    ) {
    }

    /**
     * GET /api/debug/stats
     */
    public function stats(?Request $request = null): Response
    {
        try {
            $stats = [
                'categorias' => \App\Models\Categoria::count(),
                'consultas' => \App\Models\Consulta::count(),
                'reservas' => \App\Models\Reserva::count(),
                'reseñas' => \App\Models\Resena::count(),
                'propiedades' => \App\Models\Propiedad::count(),
                'usuarios' => \App\Models\Usuario::count(),
                'favoritos' => \App\Models\Favorito::count(),
                'provincias' => \App\Models\Provincia::count(),
                'localidades' => \App\Models\Localidad::count(),
                'logs_actividad' => \App\Models\LogActividad::count(),
                'roles' => \App\Models\Rol::count(),
                'servicios' => \App\Models\Servicio::count(),
                'propiedad_servicio' => \App\Models\PropiedadServicio::count(),
                'propiedad_imagenes' => \App\Models\PropiedadImagen::count()
            ];

            $totalRegistros = array_sum($stats);

            return Response::success([
                'total_entidades' => count($stats),
                'total_registros' => $totalRegistros,
                'detalle_por_entidad' => $stats,
                'total_logs' => count(\App\Debug\Debugger::getStats()),
                'debug_enabled' => true
            ]);
        // @codeCoverageIgnoreStart
        } catch (\Throwable $e) {
            return Response::serverError($e->getMessage());
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * GET /api/debug/logs
     */
    public function logs(?Request $request = null): Response
    {
        $logFile = \App\Debug\Debugger::getLogFile();

        if (!file_exists($logFile)) {
            return Response::success(
                [],
                200,
                'No hay archivo de logs'
            );
        }

        $content = file_get_contents($logFile);
        $lines = explode(PHP_EOL, trim((string) $content));
        $logs = [];

        foreach ($lines as $line) {
            if ($line) {
                $decoded = json_decode($line, true);
                if (is_array($decoded)) {
                    $logs[] = $decoded;
                }
            }
        }

        return Response::success(array_reverse($logs));
    }

    /**
     * POST /api/debug/clear-log
     */
    public function clearLog(?Request $request = null): Response
    {
        $logFile = \App\Debug\Debugger::getLogFile();

        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
            $message = 'Log limpiado exitosamente';
        } else {
            $message = 'No existe archivo de log para limpiar';
        }

        return Response::success([], 200, $message);
    }

    /**
     * GET /api/debug/test-db
     */
    public function testDB(?Request $request = null): Response
    {
        try {
            $test = \App\Models\Categoria::first();

            return Response::success([
                'test' => $test ? 'OK - Conexión funcionando' : 'Sin datos pero conexión ok',
                'database' => 'sistema_alquiler_db',
                'driver' => 'Eloquent ORM'
            ]);
        // @codeCoverageIgnoreStart
        } catch (\Throwable $e) {
            return Response::serverError('Error de conexi��n: ' . $e->getMessage());
        }
        // @codeCoverageIgnoreEnd

    }

    /**
     * GET /api/debug/phpinfo
     */
    public function phpinfo(?Request $request = null): Response
    {
        ob_start();
        phpinfo();
        $info = (string) ob_get_clean();

        return new Response(200, ['phpinfo' => $info]);
    }
}



