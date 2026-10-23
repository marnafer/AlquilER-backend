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

    private function direccionValida(string $direccion): bool
    {
        return $direccion !== ''
            && !$this->tieneSaltosDeLinea($direccion)
            && filter_var($direccion, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function tieneSaltosDeLinea(string $valor): bool
    {
        return str_contains($valor, "\r") || str_contains($valor, "\n");
    }

    public function enviar(
        string $to,
        string $subject,
        string $htmlBody,
        string $textPlain,
        ?string $replyTo = null
    ): bool {
        return $this->enviarConEstado(
            $to,
            $subject,
            $htmlBody,
            $textPlain,
            $replyTo
        )->esExitoso();
    }

    public function enviarConEstado(
        string $to,
        string $subject,
        string $htmlBody,
        string $textPlain,
        ?string $replyTo = null
    ): EstadoEnvio {
        $destinatario = trim($to);
        $asunto = trim($subject);
        $remitente = trim($this->from ?: self::DEFAULT_FROM);
        $responderA = $replyTo !== null ? trim($replyTo) : '';

        if (
            $destinatario === ''
            || $asunto === ''
            || !$this->direccionValida($destinatario)
            || !$this->direccionValida($remitente)
            || $this->tieneSaltosDeLinea($asunto)
            || ($responderA !== '' && !$this->direccionValida($responderA))
        ) {
            return EstadoEnvio::Fallido;
        }

        $mensaje = $this->construirMime(
            $destinatario,
            $asunto,
            $htmlBody,
            $textPlain,
            $responderA !== '' ? $responderA : null
        );

        if (!$this->host) {
            return $this->guardarEnLog(
                $destinatario,
                $asunto,
                $mensaje,
                'sin SMTP configurado'
            )
                ? EstadoEnvio::GuardadoLocalmente
                : EstadoEnvio::Fallido;
        }

        try {
            $this->enviarSmtp(
                $destinatario,
                $asunto,
                $mensaje
            );

            return EstadoEnvio::Enviado;
        } catch (\Throwable $e) {
            Debugger::log(
                'Error al enviar email por SMTP',
                ['error' => $e->getMessage()],
                'ERROR'
            );

            $guardado = $this->guardarEnLog(
                $destinatario,
                $asunto,
                $mensaje,
                'fallo SMTP: ' . $e->getMessage()
            );

            return $guardado
                ? EstadoEnvio::GuardadoTrasFallo
                : EstadoEnvio::Fallido;
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
        string $mensaje,
        string $motivo = 'sin SMTP configurado'
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
            // @codeCoverageIgnoreStart
            @mkdir($directorio, 0777, true);
            // @codeCoverageIgnoreEnd
        }

        $guardado = @file_put_contents($archivo, $linea, FILE_APPEND) !== false;

        Debugger::log(
            $guardado
                ? 'Email guardado localmente'
                : 'No se pudo guardar el email localmente',
            [
                'motivo' => $motivo,
                'para' => $to,
                'asunto' => $subject,
                'archivo' => $archivo,
            ],
            $guardado ? 'INFO' : 'ERROR'
        );

        return $guardado;
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
                'verify_peer' => true,
                'verify_peer_name' => true,
                'peer_name' => $host,
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

        $validar = function (
            string $respuesta,
            array $codigos,
            string $etapa
        ): void {
            $codigo = substr($respuesta, 0, 3);

            if (!in_array($codigo, $codigos, true)) {
                throw new \RuntimeException(
                    'El servidor SMTP rechazó ' . $etapa . ': ' . $respuesta
                );
            }
        };

        try {
            $respuesta = $leer();
            $validar($respuesta, ['220'], 'la conexión');

            // Iniciar conversación SIEMPRE con EHLO (STARTTLS requiere EHLO)
            $respuesta = $enviar('EHLO alquiler.local');
            $validar($respuesta, ['250'], 'EHLO');

            // Las capacidades del EHLO siguen siendo válidas si STARTTLS no
            // está disponible y la conexión continúa en texto plano.
            $capacidades = $respuesta;

            // STARTTLS opcional
            if (!$esSsl && $this->encryption === 'tls') {
                $respuesta = $enviar('STARTTLS');

                if (str_starts_with($respuesta, '220')) {
                    // @codeCoverageIgnoreStart
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

                    $respuesta = $enviar('EHLO alquiler.local');
                    $validar($respuesta, ['250'], 'EHLO tras STARTTLS');
                    $capacidades = $respuesta;
                    // @codeCoverageIgnoreEnd
                } elseif (!str_starts_with($respuesta, '5')) {
                    throw new \RuntimeException(
                        'El servidor SMTP respondió de forma inesperada a STARTTLS: '
                        . $respuesta
                    );
                }
            }

            // Autenticación si hay credenciales
            if (
                $this->username !== null
                && $this->username !== ''
                && str_contains($capacidades, 'AUTH')
            ) {
                $respuesta = $enviar('AUTH LOGIN');
                $validar($respuesta, ['334'], 'AUTH LOGIN');

                $respuesta = $enviar(base64_encode($this->username));
                $validar($respuesta, ['334'], 'el usuario de autenticación');

                $respuesta = $enviar(base64_encode((string) $this->password));
                $validar($respuesta, ['235'], 'la autenticación');
            }

            $respuesta = $enviar('MAIL FROM:<' . ($this->from ?: self::DEFAULT_FROM) . '>');
            $validar($respuesta, ['250'], 'MAIL FROM');

            $respuesta = $enviar('RCPT TO:<' . $to . '>');
            $validar($respuesta, ['250', '251'], 'RCPT TO');

            $respuesta = $enviar('DATA');
            $validar($respuesta, ['354'], 'el inicio del mensaje');

            fwrite($socket, $mensaje . "\r\n.\r\n");
            $respuesta = $leer();
            $validar($respuesta, ['250'], 'el mensaje');

            $enviar('QUIT');

            return true;
        } finally {
            if (is_resource($socket)) {
                fclose($socket);
            }
        }
    }
}