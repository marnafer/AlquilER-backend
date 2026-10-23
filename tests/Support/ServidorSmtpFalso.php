<?php

declare(strict_types=1);

/**
 * Servidor SMTP de un solo uso para probar MailService::enviarSmtp.
 *
 * Uso: php ServidorSmtpFalso.php <puerto> <archivo-bandera> [modo]
 *
 * Escribe "1" en <archivo-bandera> cuando el socket queda escuchando para
 * que el test sepa que puede conectarse, acepta una unica conexion y
 * responde una conversacion SMTP minima. El modo "rechaza-data" hace que
 * el comando DATA responda con un error.
 */

$puerto = (int) ($argv[1] ?? 0);
$bandera = $argv[2] ?? '';
$modo = $argv[3] ?? '';

$servidor = @stream_socket_server(
    'tcp://127.0.0.1:' . $puerto,
    $codigo,
    $mensaje
);

if ($servidor === false) {
    fwrite(STDERR, 'No se pudo abrir el servidor SMTP: ' . $mensaje . PHP_EOL);
    exit(1);
}

if ($bandera !== '') {
    @file_put_contents($bandera, '1');
}

$conexion = @stream_socket_accept($servidor, 20);

if ($conexion === false) {
    fclose($servidor);
    exit(2);
}

$leerLinea = static function ($conexion): string {
    $linea = '';

    while (($caracter = fgetc($conexion)) !== false) {
        $linea .= $caracter;

        if (str_ends_with($linea, "\r\n")) {
            break;
        }
    }

    return $linea;
};

fwrite($conexion, "220 alquiler.local ESMTP\r\n");

while (($linea = $leerLinea($conexion)) !== '') {
    $comando = strtoupper(trim($linea));

    if (str_starts_with($comando, 'EHLO') || str_starts_with($comando, 'HELO')) {
        fwrite(
            $conexion,
            "250-alquiler.local\r\n250-AUTH LOGIN PLAIN\r\n250 HELP\r\n"
        );
        continue;
    }

    if (str_starts_with($comando, 'MAIL FROM')) {
        fwrite($conexion, "250 OK\r\n");
        continue;
    }

    if (str_starts_with($comando, 'RCPT TO')) {
        fwrite($conexion, "250 OK\r\n");
        continue;
    }

    if ($comando === 'DATA') {
        if ($modo === 'rechaza-data') {
            fwrite($conexion, "500 Error de prueba\r\n");
            continue;
        }

        fwrite($conexion, "354 End data with <CR><LF>.<CR><LF>\r\n");

        while (($cuerpo = $leerLinea($conexion)) !== '') {
            if (trim($cuerpo) === '.') {
                break;
            }
        }

        fwrite($conexion, "250 OK queued\r\n");
        continue;
    }

    if ($comando === 'QUIT') {
        fwrite($conexion, "221 Bye\r\n");
        break;
    }

    fwrite($conexion, "250 OK\r\n");
}

fclose($conexion);
fclose($servidor);
