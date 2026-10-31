<?php

declare(strict_types=1);

namespace Tests\Integration;

use Firebase\JWT\JWT;
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;
use Dotenv\Dotenv;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class PropiedadImagenHttpTest extends TestCase
{
    private Capsule $capsule;

    /** @var \Illuminate\Database\Connection */
    private $db;

    private ?int $usuarioId = null;
    private ?int $propiedadId = null;
    private ?int $categoriaId = null;
    private ?int $localidadId = null;
    private ?int $provinciaId = null;

    private bool $categoriaCreada = false;
    private bool $localidadCreada = false;
    private bool $provinciaCreada = false;

    private ?string $imagenTemporal = null;

    private string $baseUrl = 'http://127.0.0.1:8000';

    protected function setUp(): void
    {
        parent::setUp();

        $root = dirname(__DIR__, 2);

        Dotenv::createImmutable($root)->safeLoad();

        if (!defined('JWT_KEY')) {
            require $root . '/config/config.php';
        }

        // Protección: este test nunca debe escribir en producción.
        self::assertSame(
            'development',
            trim((string) ($_ENV['APP_ENV'] ?? '')),
            'La prueba solo puede ejecutarse con APP_ENV=development.'
        );

        self::assertSame(
            'sistema_alquiler_db_dev',
            trim((string) ($_ENV['DB_DATABASE'] ?? '')),
            'La prueba solo puede ejecutarse sobre sistema_alquiler_db_dev.'
        );

        self::assertTrue(
            function_exists('curl_init'),
            'La extensión cURL de PHP es necesaria para esta prueba.'
        );

        self::assertTrue(
            function_exists('imagecreatetruecolor')
                && function_exists('imagejpeg'),
            'La extensión GD debe estar habilitada para generar la imagen de prueba.'
        );

        $this->capsule = new Capsule();

        $this->capsule->addConnection([
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'],
            'database' => $_ENV['DB_DATABASE'],
            'username' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
        ]);

        $this->db = $this->capsule->getConnection();

        self::assertSame(
            'sistema_alquiler_db_dev',
            $this->db->getDatabaseName(),
            'La conexión efectiva no apunta a la base de desarrollo esperada.'
        );

        $this->crearDatosDePrueba();
    }

    public function test_subida_http_real_de_imagen_de_propiedad(): void
    {
        $imagen = $this->crearImagenTemporal();

        $payload = [
            'iss' => 'alquiler-backend',
            'iat' => time(),
            'exp' => time() + 300,
            'sub' => $this->usuarioId,
            'email' => 'integracion-' . $this->usuarioId . '@example.test',
            'rol_id' => 1,
        ];

        $token = JWT::encode(
            $payload,
            JWT_KEY,
            JWT_ALGORITHM
        );

        $curl = curl_init(
            $this->baseUrl . '/api/propiedad-imagenes'
        );

        self::assertNotFalse($curl);

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => [
                'propiedad_id' => (string) $this->propiedadId,
                'imagen' => new \CURLFile(
                    $imagen,
                    'image/jpeg',
                    'integracion.jpg'
                ),
            ],
        ]);

        $body = curl_exec($curl);
        $error = curl_error($curl);
        $status = (int) curl_getinfo(
            $curl,
            CURLINFO_HTTP_CODE
        );

        curl_close($curl);

        self::assertNotFalse(
            $body,
            'No se pudo realizar la solicitud HTTP: ' . $error
        );

        self::assertSame(
            201,
            $status,
            "Se esperaba HTTP 201, se recibió HTTP {$status}.\n"
                . "Respuesta: {$body}"
        );

        $registro = $this->db
            ->table('propiedad_imagenes')
            ->where('propiedad_id', $this->propiedadId)
            ->first();

        self::assertNotNull(
            $registro,
            'La API respondió correctamente, pero no se encontró el registro en la base de datos.'
        );

        self::assertSame(
            (int) $this->propiedadId,
            (int) $registro->propiedad_id
        );

        self::assertSame(
            1,
            (int) $registro->es_principal,
            'La primera imagen de la propiedad debería quedar como principal.'
        );

        self::assertNotEmpty(
            $registro->ruta,
            'El registro debe contener la ruta de la imagen guardada.'
        );
    }

    private function crearDatosDePrueba(): void
    {
        // Reutiliza una categoría activa si ya existe.
        $categoria = $this->db
            ->table('categorias')
            ->whereNull('deleted_at')
            ->first();

        if ($categoria !== null) {
            $this->categoriaId = (int) $categoria->id;
        } else {
            $this->categoriaId = (int) $this->db
                ->table('categorias')
                ->insertGetId([
                    'nombre' => 'Integracion ' . bin2hex(random_bytes(4)),
                ]);

            $this->categoriaCreada = true;
        }

        // Reutiliza una localidad activa si existe.
        $localidad = $this->db
            ->table('localidades')
            ->whereNull('deleted_at')
            ->first();

        if ($localidad !== null) {
            $this->localidadId = (int) $localidad->id;
        } else {
            $provincia = $this->db
                ->table('provincias')
                ->whereNull('deleted_at')
                ->first();

            if ($provincia !== null) {
                $this->provinciaId = (int) $provincia->id;
            } else {
                $this->provinciaId = (int) $this->db
                    ->table('provincias')
                    ->insertGetId([
                        'nombre' => 'Integracion '
                            . bin2hex(random_bytes(4)),
                    ]);

                $this->provinciaCreada = true;
            }

            $this->localidadId = (int) $this->db
                ->table('localidades')
                ->insertGetId([
                    'nombre' => 'Integracion '
                        . bin2hex(random_bytes(4)),
                    'codigo_postal' => '0000',
                    'provincia_id' => $this->provinciaId,
                ]);

            $this->localidadCreada = true;
        }

        $sufijo = bin2hex(random_bytes(6));
        $email = 'integracion-' . $sufijo . '@example.test';

        $this->usuarioId = (int) $this->db
            ->table('usuarios')
            ->insertGetId([
                'nombre' => 'Usuario',
                'apellido' => 'Integracion',
                'email' => $email,
                'telefono' => '0000000000',
                'domicilio' => 'Domicilio de prueba',
                'contrasena' => password_hash(
                    bin2hex(random_bytes(16)),
                    PASSWORD_DEFAULT
                ),
                'rol_id' => 1,
            ]);

        $this->propiedadId = (int) $this->db
            ->table('propiedades')
            ->insertGetId([
                'titulo' => 'Propiedad de integración ' . $sufijo,
                'descripcion' => 'Registro temporal para PHPUnit.',
                'precio' => 100000,
                'expensas' => 0,
                'direccion' => 'Dirección de prueba',
                'cantidad_ambientes' => 2,
                'cantidad_dormitorios' => 1,
                'cantidad_banos' => 1,
                'capacidad' => 2,
                'acepta_mascotas' => 1,
                'acepta_hijos' => 1,
                'disponible' => 1,
                'destacada' => 0,
                'categoria_id' => $this->categoriaId,
                'usuario_id' => $this->usuarioId,
                'localidad_id' => $this->localidadId,
            ]);
    }

    private function crearImagenTemporal(
        int $ancho = 200,
        int $alto = 200
    ): string {
        $imagen = imagecreatetruecolor($ancho, $alto);

        if ($imagen === false) {
            self::fail('No se pudo crear la imagen temporal.');
        }

        $fondo = imagecolorallocate($imagen, 230, 230, 230);
        imagefill($imagen, 0, 0, $fondo);

        $ruta = tempnam(
            sys_get_temp_dir(),
            'alquiler_integracion_'
        );

        if ($ruta === false) {
            imagedestroy($imagen);
            self::fail('No se pudo crear el archivo temporal.');
        }

        // Se utiliza una extensión .jpg para identificar correctamente
        // el formato de la imagen durante su procesamiento.
        $rutaJpg = $ruta . '.jpg';
        rename($ruta, $rutaJpg);

        $guardada = imagejpeg($imagen, $rutaJpg, 90);
        imagedestroy($imagen);

        if (!$guardada) {
            @unlink($rutaJpg);
            self::fail('No se pudo guardar la imagen JPEG temporal.');
        }

        $this->imagenTemporal = $rutaJpg;

        return $rutaJpg;
    }

    protected function tearDown(): void
    {
        try {
            if (isset($this->db)) {
                if ($this->propiedadId !== null) {
                    $imagenes = $this->db
                        ->table('propiedad_imagenes')
                        ->where('propiedad_id', $this->propiedadId)
                        ->get();

                    foreach ($imagenes as $imagen) {
                        $rutaRelativa = ltrim(
                            (string) $imagen->ruta,
                            '/'
                        );

                        $rutaFisica = dirname(__DIR__, 2)
                            . '/public/' . $rutaRelativa;

                        if (is_file($rutaFisica)) {
                            @unlink($rutaFisica);
                        }
                    }

                    $this->db
                        ->table('propiedad_imagenes')
                        ->where('propiedad_id', $this->propiedadId)
                        ->delete();

                    $this->db
                        ->table('propiedades')
                        ->where('id', $this->propiedadId)
                        ->delete();
                }

                if ($this->usuarioId !== null) {
                    $this->db
                        ->table('usuarios')
                        ->where('id', $this->usuarioId)
                        ->delete();
                }

                if (
                    $this->localidadCreada
                    && $this->localidadId !== null
                ) {
                    $this->db
                        ->table('localidades')
                        ->where('id', $this->localidadId)
                        ->delete();
                }

                if (
                    $this->provinciaCreada
                    && $this->provinciaId !== null
                ) {
                    $this->db
                        ->table('provincias')
                        ->where('id', $this->provinciaId)
                        ->delete();
                }

                if (
                    $this->categoriaCreada
                    && $this->categoriaId !== null
                ) {
                    $this->db
                        ->table('categorias')
                        ->where('id', $this->categoriaId)
                        ->delete();
                }
            }
        } finally {
            if (
                $this->imagenTemporal !== null
                && is_file($this->imagenTemporal)
            ) {
                @unlink($this->imagenTemporal);
            }

            parent::tearDown();
        }
    }

    
    public function test_rechaza_imagen_http_que_supera_15_mb(): void
    {
        $ruta = tempnam(sys_get_temp_dir(), 'alquiler_grande_');

        self::assertNotFalse($ruta);

        try {
            // 15 MiB + 1 byte.
            $tamano = 15 * 1024 * 1024 + 1;

            $archivo = fopen($ruta, 'wb');
            self::assertNotFalse($archivo);

            try {
                $restantes = $tamano;

                while ($restantes > 0) {
                    $bloque = min($restantes, 1024 * 1024);
                    $escritos = fwrite($archivo, str_repeat('A', $bloque));

                    self::assertNotFalse($escritos);
                    self::assertGreaterThan(0, $escritos);

                    $restantes -= $escritos;
                }
            } finally {
                fclose($archivo);
            }

            $payload = [
                'iss' => 'alquiler-backend',
                'iat' => time(),
                'exp' => time() + 300,
                'sub' => $this->usuarioId,
                'email' => 'integracion-' . $this->usuarioId . '@example.test',
                'rol_id' => 1,
            ];

            $token = JWT::encode($payload, JWT_KEY, JWT_ALGORITHM);

            $curl = curl_init($this->baseUrl . '/api/propiedad-imagenes');

            self::assertNotFalse($curl);

            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Accept: application/json',
                ],
                CURLOPT_POSTFIELDS => [
                    'propiedad_id' => (string) $this->propiedadId,
                    'imagen' => new \CURLFile(
                        $ruta,
                        'image/jpeg',
                        'imagen_grande.jpg'
                    ),
                ],
            ]);

            try {
                $body = curl_exec($curl);
                $error = curl_error($curl);
                $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            } finally {
                curl_close($curl);
            }

            self::assertNotFalse(
                $body,
                'Falló la solicitud HTTP: ' . $error
            );

            self::assertSame(
                422,
                $status,
                "Se esperaba HTTP 422 para una imagen mayor de 15 MB. "
                    . "Se recibió HTTP {$status}.\nRespuesta: {$body}"
            );

            self::assertSame(
                0,
                $this->db->table('propiedad_imagenes')
                    ->where('propiedad_id', $this->propiedadId)
                    ->count(),
                'No debería guardarse una imagen que supera el límite.'
            );
        } finally {
            if (is_file($ruta)) {
                @unlink($ruta);
            }
        }
    }

    
    public function test_rechaza_imagen_http_con_dimension_excesiva(): void
    {
        $imagen = $this->crearImagenTemporal(12001, 1);

        $payload = [
            'iss' => 'alquiler-backend',
            'iat' => time(),
            'exp' => time() + 300,
            'sub' => $this->usuarioId,
            'email' => 'integracion-' . $this->usuarioId . '@example.test',
            'rol_id' => 1,
        ];

        $token = JWT::encode($payload, JWT_KEY, JWT_ALGORITHM);

        $curl = curl_init($this->baseUrl . '/api/propiedad-imagenes');

        self::assertNotFalse($curl);

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => [
                'propiedad_id' => (string) $this->propiedadId,
                'imagen' => new \CURLFile(
                    $imagen,
                    'image/jpeg',
                    'dimension_excesiva.jpg'
                ),
            ],
        ]);

        try {
            $body = curl_exec($curl);
            $error = curl_error($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        } finally {
            curl_close($curl);
        }

        self::assertNotFalse(
            $body,
            'Falló la solicitud HTTP: ' . $error
        );

        self::assertSame(
            422,
            $status,
            "Se esperaba HTTP 422 para dimensiones excesivas. "
                . "Se recibió HTTP {$status}.\nRespuesta: {$body}"
        );

        self::assertSame(
            0,
            $this->db->table('propiedad_imagenes')
                ->where('propiedad_id', $this->propiedadId)
                ->count(),
            'No debería guardarse un registro para una imagen rechazada.'
        );
    }


}
