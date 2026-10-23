<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Debug\Debugger;
use App\Services\EstadoEnvio;
use App\Services\MailService;
use PHPUnit\Framework\TestCase;

final class MailServiceTest extends TestCase
{
    /** @var array<string, string|null> */
    private array $respaldo = [];

    /** @var list<resource> */
    private array $procesosSmtp = [];

    /** @var list<string> */
    private array $banderasSmtp = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ($this->archivosTocados() as $archivo) {
            $this->respaldo[$archivo] = is_file($archivo)
                ? (string) file_get_contents($archivo)
                : null;
        }
    }

    protected function tearDown(): void
    {
        // MailService::guardarEnLog escribe en storage/emails.log y en el
        // log de Debugger; se restauran para no dejar efectos secundarios.
        foreach ($this->respaldo as $archivo => $contenido) {
            @unlink($archivo);
            if ($contenido !== null) {
                file_put_contents($archivo, $contenido);
            }
        }

        foreach ($this->procesosSmtp as $proceso) {
            if (is_resource($proceso)) {
                @proc_terminate($proceso);
                @proc_close($proceso);
            }
        }

        foreach ($this->banderasSmtp as $bandera) {
            @unlink($bandera);
        }

        parent::tearDown();

        unset($_ENV['MAIL_HOST']);
        unset($_ENV['MAIL_PORT']);
        unset($_ENV['MAIL_FROM']);
        unset($_ENV['MAIL_FROM_NAME']);
        unset($_ENV['MAIL_ENCRYPTION']);
    }

    /**
     * @return list<string>
     */
    private function archivosTocados(): array
    {
        return [
            Debugger::getLogFile(),
            dirname(__DIR__, 3)
                . DIRECTORY_SEPARATOR . 'storage'
                . DIRECTORY_SEPARATOR . 'emails.log',
        ];
    }

    public function test_enviar_sin_host_guarda_en_log_y_devuelve_true(): void
    {
        $mail = new MailService();

        $resultado = $mail->enviar(
            'destino@test.com',
            'Asunto de prueba',
            '<p>Hola</p>',
            'Hola'
        );

        $this->assertTrue($resultado);
    }

    public function test_enviar_sin_destinatario_devuelve_false(): void
    {
        $mail = new MailService(
            null,
            null,
            null,
            null,
            'emisor@test.com'
        );

        $resultado = $mail->enviar(
            '   ',
            'Asunto',
            '<p>Hola</p>',
            'Hola'
        );

        $this->assertFalse($resultado);
    }

    public function test_enviar_sin_asunto_devuelve_false(): void
    {
        $mail = new MailService();

        $resultado = $mail->enviar(
            'destino@test.com',
            ' ',
            '<p>Hola</p>',
            'Hola'
        );

        $this->assertFalse($resultado);
    }

    public function test_desde_entorno_toma_los_valores_del_entorno(): void
    {
        $_ENV['MAIL_HOST'] = 'smtp.test.com';
        $_ENV['MAIL_PORT'] = '2525';
        $_ENV['MAIL_FROM'] = 'origen@test.com';
        $_ENV['MAIL_FROM_NAME'] = 'Origen Test';
        $_ENV['MAIL_ENCRYPTION'] = 'ssl';

        $mail = MailService::desdeEntorno();

        $reflexion = new \ReflectionClass($mail);

        $this->assertSame('smtp.test.com', $reflexion->getProperty('host')->getValue($mail));
        $this->assertSame(2525, $reflexion->getProperty('port')->getValue($mail));
        $this->assertSame('origen@test.com', $reflexion->getProperty('from')->getValue($mail));
        $this->assertSame('Origen Test', $reflexion->getProperty('fromName')->getValue($mail));
        $this->assertSame('ssl', $reflexion->getProperty('encryption')->getValue($mail));
    }

    public function test_desde_entorno_usa_valores_por_defecto_cuando_faltan(): void
    {
        $mail = MailService::desdeEntorno();

        $reflexion = new \ReflectionClass($mail);

        $this->assertNull($reflexion->getProperty('host')->getValue($mail));
        $this->assertNull($reflexion->getProperty('port')->getValue($mail));
        $this->assertSame('no-responder@alquiler.local', $reflexion->getProperty('from')->getValue($mail));
        $this->assertSame('AlquilER', $reflexion->getProperty('fromName')->getValue($mail));
        $this->assertSame('tls', $reflexion->getProperty('encryption')->getValue($mail));
    }

    public function test_enviar_sin_host_con_reply_to_y_cuerpo_solo_html(): void
    {
        $mail = new MailService(
            null,
            null,
            null,
            null,
            'emisor@test.com',
            'Emisor'
        );

        $resultado = $mail->enviar(
            'destino@test.com',
            'Asunto',
            '<p>Hola</p><br>Adios',
            '',
            'reply@test.com'
        );

        $this->assertTrue($resultado);
    }

    public function test_enviar_con_host_sin_servidor_devuelve_false(): void
    {
        $puerto = $this->puertoLibre();

        $mail = new MailService(
            '127.0.0.1',
            $puerto,
            null,
            null,
            'emisor@test.com',
            'Emisor',
            'none'
        );

        $resultado = $mail->enviar(
            'destino@test.com',
            'Asunto',
            '<p>Hola</p>',
            'Hola'
        );

        $this->assertFalse($resultado);
    }

    public function test_enviar_con_servidor_smtp_devuelve_true(): void
    {
        $puerto = $this->puertoLibre();
        $this->iniciarServidorSmtp($puerto);

        $mail = new MailService(
            '127.0.0.1',
            $puerto,
            'usuario',
            'secreto',
            'emisor@test.com',
            'Emisor',
            'none'
        );

        $resultado = $mail->enviar(
            'destino@test.com',
            'Asunto',
            '<p>Hola</p>',
            'Hola'
        );

        $this->assertTrue($resultado);
    }

    public function test_enviar_con_host_encriptacion_desconocida_usa_smtp(): void
    {
        $puerto = $this->puertoLibre();
        $this->iniciarServidorSmtp($puerto);

        $mail = new MailService(
            '127.0.0.1',
            $puerto,
            null,
            null,
            'emisor@test.com',
            'Emisor',
            'plain'
        );

        $resultado = $mail->enviar(
            'destino@test.com',
            'Asunto',
            '<p>Hola</p>',
            'Hola'
        );

        $this->assertTrue($resultado);
    }

    public function test_enviar_con_starttls_no_soportado_continua_en_plano(): void
    {
        $puerto = $this->puertoLibre();
        $this->iniciarServidorSmtp($puerto);

        $mail = new MailService(
            '127.0.0.1',
            $puerto,
            null,
            null,
            'emisor@test.com',
            'Emisor',
            'tls'
        );

        $resultado = $mail->enviar(
            'destino@test.com',
            'Asunto',
            '<p>Hola</p>',
            'Hola'
        );

        $this->assertTrue($resultado);
    }

    public function test_enviar_devuelve_false_si_el_servidor_rechaza_data(): void
    {
        $puerto = $this->puertoLibre();
        $this->iniciarServidorSmtp($puerto, 'rechaza-data');

        $mail = new MailService(
            '127.0.0.1',
            $puerto,
            null,
            null,
            'emisor@test.com',
            'Emisor',
            'none'
        );

        $resultado = $mail->enviar(
            'destino@test.com',
            'Asunto',
            '<p>Hola</p>',
            'Hola'
        );

        $this->assertFalse($resultado);
    }

    public function test_enviar_devuelve_false_cuando_el_servidor_rechaza_una_etapa(): void
    {
        $casos = [
            'rechaza-saludo' => ['none', null],
            'rechaza-ehlo' => ['none', null],
            'starttls-inesperado' => ['tls', null],
            'rechaza-auth' => ['none', 'usuario'],
            'rechaza-usuario' => ['none', 'usuario'],
            'rechaza-clave' => ['none', 'usuario'],
            'rechaza-mail' => ['none', null],
            'rechaza-rcpt' => ['none', null],
            'rechaza-data' => ['none', null],
            'rechaza-fin-data' => ['none', null],
        ];

        foreach ($casos as $modo => [$encryption, $usuario]) {
            $puerto = $this->puertoLibre();
            $this->iniciarServidorSmtp($puerto, $modo);

            $mail = new MailService(
                '127.0.0.1',
                $puerto,
                $usuario,
                $usuario !== null ? 'secreto' : null,
                'emisor@test.com',
                'Emisor',
                $encryption
            );

            $resultado = $mail->enviar(
                'destino@test.com',
                'Asunto',
                '<p>Hola</p>',
                'Hola'
            );

            $this->assertFalse($resultado, 'Modo: ' . $modo);
        }
    }

    public function test_enviar_con_estado_sin_host_indica_guardado_local(): void
    {
        $mail = new MailService();

        $estado = $mail->enviarConEstado(
            'destino@test.com',
            'Asunto',
            '<p>Hola</p>',
            'Hola'
        );

        $this->assertSame(EstadoEnvio::GuardadoLocalmente, $estado);
        $this->assertTrue($estado->fueGuardadoLocalmente());
        $this->assertFalse($estado->fueEnviado());
        $this->assertTrue($estado->esExitoso());
    }

    public function test_enviar_con_estado_smtp_exitoso_indica_enviado(): void
    {
        $puerto = $this->puertoLibre();
        $this->iniciarServidorSmtp($puerto);

        $mail = new MailService(
            '127.0.0.1',
            $puerto,
            null,
            null,
            'emisor@test.com',
            'Emisor',
            'none'
        );

        $estado = $mail->enviarConEstado(
            'destino@test.com',
            'Asunto',
            '<p>Hola</p>',
            'Hola'
        );

        $this->assertSame(EstadoEnvio::Enviado, $estado);
        $this->assertTrue($estado->fueEnviado());
        $this->assertFalse($estado->fueGuardadoLocalmente());
        $this->assertTrue($estado->esExitoso());
    }

    public function test_enviar_con_estado_fallo_smtp_guarda_local_pero_no_es_exitoso(): void
    {
        $puerto = $this->puertoLibre();

        $mail = new MailService(
            '127.0.0.1',
            $puerto,
            null,
            null,
            'emisor@test.com',
            'Emisor',
            'none'
        );

        $estado = $mail->enviarConEstado(
            'destino@test.com',
            'Asunto',
            '<p>Hola</p>',
            'Hola'
        );

        $this->assertSame(EstadoEnvio::GuardadoTrasFallo, $estado);
        $this->assertTrue($estado->fueGuardadoLocalmente());
        $this->assertFalse($estado->fueEnviado());
        $this->assertFalse($estado->esExitoso());

        $emails = (string) file_get_contents(
            dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage'
                . DIRECTORY_SEPARATOR . 'emails.log'
        );

        $this->assertStringContainsString('destino@test.com', $emails);
        $this->assertStringContainsString(
            'Email guardado localmente',
            (string) file_get_contents(Debugger::getLogFile())
        );
        $this->assertStringContainsString(
            'fallo SMTP',
            (string) file_get_contents(Debugger::getLogFile())
        );
    }

    public function test_enviar_con_estado_invalido_es_fallido(): void
    {
        $mail = new MailService();

        $estado = $mail->enviarConEstado(
            '   ',
            'Asunto',
            '<p>Hola</p>',
            'Hola'
        );

        $this->assertSame(EstadoEnvio::Fallido, $estado);
        $this->assertFalse($estado->esExitoso());
    }

    public function test_autentica_cuando_starttls_no_esta_disponible(): void
    {
        $puerto = $this->puertoLibre();
        $this->iniciarServidorSmtp($puerto, 'requiere-auth');

        $mail = new MailService(
            '127.0.0.1',
            $puerto,
            'usuario',
            'secreto',
            'emisor@test.com',
            'Emisor',
            'tls'
        );

        $resultado = $mail->enviar(
            'destino@test.com',
            'Asunto',
            '<p>Hola</p>',
            'Hola'
        );

        $this->assertTrue($resultado);
    }

    private function puertoLibre(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $codigo, $mensaje);

        if ($socket === false) {
            $this->fail('No se pudo reservar un puerto libre: ' . $mensaje);
        }

        $nombre = (string) stream_socket_get_name($socket, false);
        fclose($socket);

        return (int) substr($nombre, strrpos($nombre, ':') + 1);
    }

    private function iniciarServidorSmtp(int $puerto, string $modo = ''): void
    {
        $script = dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR . 'Support'
            . DIRECTORY_SEPARATOR . 'ServidorSmtpFalso.php';

        $bandera = tempnam(sys_get_temp_dir(), 'smtp');
        @unlink($bandera);

        $comando = [PHP_BINARY, $script, (string) $puerto, $bandera];

        if ($modo !== '') {
            $comando[] = $modo;
        }

        $proceso = proc_open(
            $comando,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if (!is_resource($proceso)) {
            $this->fail('No se pudo iniciar el servidor SMTP falso.');
        }

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        $this->procesosSmtp[] = $proceso;
        $this->banderasSmtp[] = $bandera;

        $intentos = 0;

        while (!is_file($bandera) && $intentos < 100) {
            usleep(50000);
            $intentos++;
        }

        if (!is_file($bandera)) {
            $this->fail('El servidor SMTP falso no quedó disponible.');
        }
    }
}