<?php
/**
 * Controlador de debug para API
 */

namespace App\Controllers;

use App\Models\Categoria;
use App\Models\Consulta;
use App\Models\Reserva;
use App\Models\Resena;
use App\Models\Propiedad;
use App\Models\Usuario;
use App\Models\Favorito;
use App\Models\Provincia;
use App\Models\Localidad;
use App\Models\LogActividad;
use App\Models\Rol;
use App\Models\Servicio;
use App\Models\PropiedadServicio;
use App\Models\PropiedadImagen;
use App\Debug\Debugger;
use Exception;

class DebugController
{
    /**
     * GET /api/debug/stats
     */
    public function stats()
    {
        try {
            // Contar registros usando Eloquent
            $stats = [
                'categorias' => Categoria::count(),
                'consultas' => Consulta::count(),
                'reservas' => Reserva::count(),
                'reseñas' => Resena::count(),
                'propiedades' => Propiedad::count(),
                'usuarios' => Usuario::count(),
                'favoritos' => Favorito::count(),
                'provincias' => Provincia::count(),
                'localidades' => Localidad::count(),
                'logs_actividad' => LogActividad::count(),
                'roles' => Rol::count(),
                'servicios' => Servicio::count(),
                'propiedad_servicio' => PropiedadServicio::count(),
                'propiedad_imagenes' => PropiedadImagen::count()
            ];
            
            $totalRegistros = array_sum($stats);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'total_entidades' => count($stats),
                    'total_registros' => $totalRegistros,
                    'detalle_por_entidad' => $stats,
                    'total_logs' => count(Debugger::getStats()),
                    'debug_enabled' => true
                ]
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * GET /api/debug/logs
     */
    public function logs()
    {
        $logFile = dirname(__DIR__, 2) . '/debug.log';
        
        if (!file_exists($logFile)) {
            echo json_encode([
                'success' => true,
                'data' => [],
                'message' => 'No hay archivo de logs'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        $content = file_get_contents($logFile);
        $lines = explode(PHP_EOL, trim($content));
        $logs = [];
        
        foreach ($lines as $line) {
            if ($line) {
                $decoded = json_decode($line, true);
                if ($decoded) {
                    $logs[] = $decoded;
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => array_reverse($logs),
            'total' => count($logs)
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * POST /api/debug/clear-log
     */
    public function clearLog()
    {
        $logFile = dirname(__DIR__, 2) . '/debug.log';
        
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
            $message = 'Log limpiado exitosamente';
        } else {
            $message = 'No existe archivo de log para limpiar';
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * GET /api/debug/test-db
     */
    public function testDB()
    {
        try {
            // Probar conexión obteniendo un registro simple
            $test = Categoria::first();
            
            echo json_encode([
                'success' => true,
                'message' => 'Conexión a base de datos exitosa',
                'data' => [
                    'test' => $test ? 'OK - Conexión funcionando' : 'Sin datos pero conexión ok',
                    'database' => 'sistema_alquiler_db',
                    'driver' => 'Eloquent ORM'
                ]
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * GET /api/debug/phpinfo
     */
    public function phpinfo()
    {
        phpinfo();
        exit;
    }
}