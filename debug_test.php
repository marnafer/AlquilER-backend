<?php
/**
 * Script de prueba para verificar todas las entidades
 * Ejecutar desde terminal: php debug_test.php
 */

// ============================================
// IMPORTANTE: Este script NO necesita database.php
// Solo prueba las APIs mediante HTTP
// ============================================

// Configurar zona horaria
date_default_timezone_set('America/Argentina/Buenos_Aires');

// ============================================
// CONFIGURACIÓN - AJUSTA SEGÚN TU PROYECTO
// ============================================

// Cambia esto según tu configuración
$baseUrl = 'http://localhost/AlquilER-backend/public';

// ============================================
// LISTA DE ENDPOINTS A PROBAR
// ============================================

$entidades = [
    'Categorías' => '/api/categorias',
    'Provincias' => '/api/provincias',
    'Localidades' => '/api/localidades',
    'Usuarios' => '/api/usuarios',
    'Propiedades' => '/api/propiedades',
    'Servicios' => '/api/servicios',
    'Propiedad Servicio' => '/api/propiedades-servicios',
    'Reservas' => '/api/reservas',
    'Reseñas' => '/api/resenas',
    'Consultas' => '/api/consultas',
    'Favoritos' => '/api/favoritos',
    'Logs Actividad' => '/api/logs-actividad',
    'Roles' => '/api/roles',
    'Propiedad Imagen' => '/api/propiedad-imagenes'
];

// ============================================
// EJECUCIÓN DE PRUEBAS
// ============================================

echo "\n";
echo "===========================================================\n";
echo "        TEST DE ENTIDADES DEL SISTEMA\n";
echo "===========================================================\n";
echo "\n";
echo "URL base: $baseUrl\n";
echo "Fecha/Hora: " . date('Y-m-d H:i:s') . "\n";
echo "\n";

$totalExitosos = 0;
$totalFallidos = 0;

foreach ($entidades as $nombre => $endpoint) {
    $url = $baseUrl . $endpoint;
    echo "Probando $nombre... ";
    
    // Usar file_get_contents (más simple, no requiere cURL)
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json\r\n',
            'timeout' => 5
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response !== false) {
        echo "✅ OK\n";
        $totalExitosos++;
    } else {
        echo "❌ ERROR\n";
        $totalFallidos++;
    }
}

echo "\n";
echo "===========================================================\n";
echo "                   RESUMEN\n";
echo "===========================================================\n";
echo "\n";
echo "✅ Exitosos: $totalExitosos\n";
echo "❌ Fallidos: $totalFallidos\n";
echo "📊 Total: " . count($entidades) . "\n";
echo "\n";

if ($totalFallidos > 0) {
    echo "💡 Sugerencias:\n";
    echo "   1. Verifica que Apache esté corriendo\n";
    echo "   2. Abre en navegador: $baseUrl\n";
    echo "   3. Revisa que las rutas API existan\n";
    echo "\n";
}

echo "===========================================================\n";
echo "                   FIN DEL TEST\n";
echo "===========================================================\n";
echo "\n";