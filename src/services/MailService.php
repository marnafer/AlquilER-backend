<?php

declare(strict_types=1);

namespace App\Services;

use App\Debug\Debugger;

/**
 * Envío de correos electrónicos.
 *
 * Si no hay servidor SMTP configurado (MAIL_HOST vacío) los mensajes se
 * persisten en storage/emails.log y se registran en debug.log, de modo que
 * en desarrollo el flujo funciona sin depender de un relay.
 */
class MailService
{
    private const DEFAULT_FROM = 'no-responder@alquiler.local';
    private const DEFAULT_FROM_NAME = 'AlquilER';

    public function __construct(
        private readonly ?string $host = null,
        private readonly ?int $port = null,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
        private readonly ?string $from = null,
        private readonly ?string $fromName = null,
        private readonly string $encryption = 'tls'
    ) {
    }

    public static function desdeEntorno(): self
    {
        $host = self::env('MAIL_HOST');

        return new self(
            $host !== '' ? $host : null,
            self::env('MAIL_PORT') !== '' ? (int) self::env('MAIL_PORT') : null,
            self::env('MAIL_USERNAME'),
            (string) ($_ENV['MAIL_PASSWORD'] ?? ''),
            self::env('MAIL_FROM', self::DEFAULT_FROM),
            self::env('MAIL_FROM_NAME', self::DEFAULT_FROM_NAME),
            strtolower(self::env('MAIL_ENCRYPTION', 'tls'))
        );
    }

    private static function env(string $clave, string $default = ''): string
    {
        return trim((string) ($_ENV[$clave] ?? $default));
    }

    public function enviar(
        string $to,
        string $subject,
        string $htmlBody,
        string $textPlain,
        ?string $replyTo = null
    ): bool {
        $destinatario = trim($to);
        $asunto = trim($subject);

        if ($destinatario === '' || $asunto === '') {
            return false;
        }

        $mensaje = $this->construirMime(
            $destinatario,
            $asunto,
            $htmlBody,
            $textPlain,
            $replyTo
        );

        if (!$this->host) {
            return $this->guardarEnLog(
                $destinatario,
                $asunto,
                $mensaje
            );
        }

        try {
            return $this->enviarSmtp(
                $destinatario,
                $asunto,
                $mensaje
            );
        } catch (\Throwable $e) {
            Debugger::log(
                'Error al enviar email por SMTP',
                ['error' => $e->getMessage()],
                'ERROR'
            );
            $this->guardarEnLog(
                $destinatario,
                $asunto,
                $mensaje
            );
            return false;
        }
    }

    private function construirMime(
        string $to,
        string $subject,
        string $htmlBody,
        string $textPlain,
        ?string $replyTo
    ): string {
        $boundary = 'alquiler-' . md5(uniqid((string) mt_rand(), true));

        $from = $this->from ?: self::DEFAULT_FROM;
        $fromName = $this->fromName ?: self::DEFAULT_FROM_NAME;

        $headers = [];
        $headers[] = 'From: ' . $this->encabezado($fromName) . ' <' . $from . '>';

        if ($replyTo !== null && trim($replyTo) !== '') {
            $headers[] = 'Reply-To: <' . trim($replyTo) . '>';
        }

        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        $headers[] = 'X-Mailer: AlquilER';

        return implode("\r\n", $headers) . "\r\n\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
            . $this->cifrarCorporal($this->textoAPlain($textPlain, $htmlBody)) . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
            . $this->cifrarCorporal($htmlBody) . "\r\n"
            . "--{$boundary}--\r\n";
    }

    private function encabezado(string $valor): string
    {
        return str_replace(
            ["\r", "\n", '<', '>'],
            '',
            trim($valor)
        );
    }

    private function textoAPlain(string $textPlain, string $htmlBody): string
    {
        if ($textPlain !== '') {
            return $textPlain;
        }

        $texto = preg_replace('/<br\s*\/?>/i', "\n", $htmlBody) ?? $htmlBody;
        $texto = strip_tags($texto);

        return trim($texto);
    }

    private function cifrarCorporal(string $texto): string
    {
        return quoted_printable_encode(rtrim($texto));
    }

    private function guardarEnLog(
        string $to,
        string $subject,
        string $mensaje
    ): bool {
        $linea = json_encode(
            [
                'fecha' => date('Y-m-d H:i:s'),
                'para' => $to,
                'asunto' => $subject,
                'mensaje' => $mensaje,
            ],
            JSON_UNESCAPED_UNICODE
        ) . PHP_EOL;

        $archivo = dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR
            . 'storage'
            . DIRECTORY_SEPARATOR
            . 'emails.log';

        $directorio = dirname($archivo);

        if (!is_dir($directorio)) {
            @mkdir($directorio, 0777, true);
        }

        @file_put_contents($archivo, $linea, FILE_APPEND);

        Debugger::log(
            'Email generado (sin SMTP configurado)',
            [
                'para' => $to,
                'asunto' => $subject,
                'archivo' => $archivo,
            ]
        );

        return true;
    }

    private function enviarSmtp(
        string $to,
        string $subject,
        string $mensaje
    ): bool {
        $puerto = $this->port ?: ($this->encryption === 'ssl' ? 465 : 587);
        $host = $this->host;

        $esSsl = $this->encryption === 'ssl';
        $prefijo = $esSsl ? 'ssl://' : '';

        $contexto = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $socket = @stream_socket_client(
            $prefijo . $host . ':' . $puerto,
            $codigoError,
            $mensajeError,
            15,
            STREAM_CLIENT_CONNECT,
            $contexto
        );

        if (!$socket) {
            throw new \RuntimeException(
                'No se pudo conectar con el servidor SMTP: ' . ($mensajeError ?: (string) $codigoError)
            );
        }

        $leer = function () use ($socket): string {
            $respuesta = '';
            while (($linea = fgets($socket, 515)) !== false) {
                $respuesta .= $linea;

                if (isset($linea[3]) && $linea[3] === ' ') {
                    break;
                }
            }

            return trim($respuesta);
        };

        $enviar = function (string $comando) use ($socket, $leer): string {
            fwrite($socket, $comando . "\r\n");
            return $leer();
        };

        $leer();

        // Iniciar conversación SIEMPRE con EHLO (STARTTLS requiere EHLO)
        $respuesta = $enviar('EHLO alquiler.local');

        // STARTTLS opcional
        if (!$esSsl && $this->encryption === 'tls') {
            $leer();
            $respuesta = $enviar('STARTTLS');

            if (str_starts_with($respuesta, '220')) {
                $tls = stream_socket_enable_crypto(
                    $socket,
                    true,
                    STREAM_CRYPTO_METHOD_TLS_CLIENT
                );

                if (!$tls) {
                    throw new \RuntimeException(
                        'No se pudo iniciar TLS con el servidor SMTP'
                    );
                }

                $leer();
                $respuesta = $enviar('EHLO alquiler.local');
            }
        }

        // Autenticación si hay credenciales
        if (
            $this->username !== null
            && $this->username !== ''
            && str_contains($respuesta, 'AUTH')
        ) {
            $leer();
            $enviar('AUTH LOGIN');
            $leer();
            $enviar(base64_encode($this->username));
            $leer();
            $respuesta = $enviar(base64_encode((string) $this->password));
        }

        $leer();
        $respuesta = $enviar('MAIL FROM:<' . ($this->from ?: self::DEFAULT_FROM) . '>');
        $leer();
        $respuesta = $enviar('RCPT TO:<' . $to . '>');
        $leer();
        $respuesta = $enviar('DATA');

        if (!str_starts_with($respuesta, '354')) {
            throw new \RuntimeException(
                'El servidor SMTP rechazó el inicio del mensaje: ' . $respuesta
            );
        }

        fwrite($socket, $mensaje . "\r\n.\r\n");
        $respuesta = $leer();

        $enviar('QUIT');
        fclose($socket);

        return str_starts_with($respuesta, '250');
    }
}