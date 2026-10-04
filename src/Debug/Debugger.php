<?php
/**
 * Debugger general para la aplicación
 */

namespace App\Debug;

class Debugger {
    
    private static $enabled = true;
    private static $logFile;
    
    /**
     * Ruta absoluta al archivo de log (raíz del proyecto).
     * Punto único de verdad para el archivo de debug.
     */
    public static function getLogFile(): string {
        if (self::$logFile === null) {
            self::$logFile = dirname(__DIR__, 2)
                . DIRECTORY_SEPARATOR
                . 'debug.log';
        }
        return self::$logFile;
    }

    /**
     * Habilitar/deshabilitar debug
     */
    public static function setEnabled($enabled) {
        self::$enabled = $enabled;
    }
    
    /**
     * Registrar mensaje en log
     */
    public static function log($message, $data = null, $type = 'INFO') {
        if (!self::$enabled) return;
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type,
            'message' => $message,
            'data' => $data
        ];
        
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        file_put_contents(self::getLogFile(), $logLine, FILE_APPEND);
    }
    
    /**
     * Debug de una variable
     */
    public static function dump($var, $label = null) {
        if (!self::$enabled) return;
        
        echo '<div style="background: #f5f5f5; border: 1px solid #ddd; margin: 10px; padding: 10px; font-family: monospace;">';
        if ($label) echo "<strong>$label:</strong><br>";
        echo '<pre>' . print_r($var, true) . '</pre>';
        echo '</div>';
    }
    
    /**
     * Debug de una variable y detiene ejecución
     */
    public static function dd($var, $label = null) {
        self::dump($var, $label);
        die();
    }
    
    /**
     * Medir tiempo de ejecución
     */
    public static function time($callback, $label = null) {
        $start = microtime(true);
        $result = $callback();
        $end = microtime(true);
        $time = round(($end - $start) * 1000, 2);
        
        self::log($label ?: 'Time measurement', ['time_ms' => $time, 'result' => $result]);
        
        return ['result' => $result, 'time_ms' => $time];
    }
    
    /**
     * Debug de consulta SQL
     */
    public static function sql($query, $params = null) {
        if (!self::$enabled) return;
        
        self::log('SQL Query', ['query' => $query, 'params' => $params]);
    }
    
    /**
     * Debug de petición HTTP
     */
    public static function request() {
        if (!self::$enabled) return;
        
        self::log('HTTP Request', [
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'headers' => getallheaders(),
            'post' => $_POST,
            'get' => $_GET,
            'input' => file_get_contents('php://input')
        ]);
    }
    
    /**
     * Debug de API response
     */
    public static function response($data, $statusCode = 200) {
        if (!self::$enabled) return;
        
        self::log('API Response', [
            'status_code' => $statusCode,
            'data' => $data
        ]);
    }
    
    /**
     * Mostrar errores en pantalla (solo desarrollo)
     */
    public static function enableErrorReporting() {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
    }
    
    /**
     * Obtener estadísticas de debug
     */
    public static function getStats() {
        $logContent = file_exists(self::getLogFile()) ? file_get_contents(self::getLogFile()) : '';
        $logLines = explode(PHP_EOL, trim($logContent));
        $logs = [];
        
        foreach ($logLines as $line) {
            if ($line) {
                $logs[] = json_decode($line, true);
            }
        }
        
        return [
            'total_logs' => count($logs),
            'log_file' => self::getLogFile(),
            'enabled' => self::$enabled
        ];
    }
    
    /**
     * Limpiar archivo de log
     */
    public static function clearLog() {
        if (file_exists(self::getLogFile())) {
            unlink(self::getLogFile());
        }
    }
}