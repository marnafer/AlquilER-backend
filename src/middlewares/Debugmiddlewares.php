<?php
/**
 * Middleware de debug para todas las peticiones
 */

namespace App\Middlewares;

use App\Debug\Debugger;

class DebugMiddleware {
    
    public static function handle() {
        // Registrar todas las peticiones
        Debugger::request();
        
        // Medir tiempo de respuesta
        $start = microtime(true);
        
        return function($response) use ($start) {
            $end = microtime(true);
            $executionTime = round(($end - $start) * 1000, 2);
            
            Debugger::log('Request completed', [
                'execution_time_ms' => $executionTime,
                'memory_usage_mb' => round(memory_get_usage() / 1024 / 1024, 2)
            ]);
            
            return $response;
        };
    }
}