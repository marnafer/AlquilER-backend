<?php

declare(strict_types=1);

/**
 * Servidor SMTP de un solo uso para probar MailService::enviarSmtp.
 *
 * Uso: php ServidorSmtpFalso.php <puerto> <archivo-bandera> [modo]
 *
 * Escribe "1" en <archivo-bandera> cuando el socket queda escuchando para
 * que el test sepa que puede conectarse, acepta una unica conexion y
 * responde una conversacion SMTP minima. El modo permite simular rechazos:
 * rechaza-saludo, rechaza-ehlo, starttls-inesperado, rechaza-auth,
 * rechaza-usuario, rechaza-clave, requiere-auth, rechaza-mail,
 * rechaza-rcpt, rechaza-data y rechaza-fin-data.
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

stream_set_timeout($conexion, 5);

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

fwrite(
    $conexion,
    $modo === 'rechaza-saludo'
        ? "554 Servicio no disponible\r\n"
        : "220 alquiler.local ESMTP\r\n"
);

$autenticando = 0;
$autenticado = false;

while (($linea = $leerLinea($conexion)) !== '') {
    $comando = strtoupper(trim($linea));

    if ($autenticando === 1) {
        if ($modo === 'rechaza-usuario') {
            $autenticando = 0;
            fwrite($conexion, "535 Usuario rechazado\r\n");
            continue;
        }

        $autenticando = 2;
        fwrite($conexion, "334 UGFzc3dvcmQ6\r\n");
        continue;
    }

    if ($autenticando === 2) {
        $autenticando = 0;

        if ($modo === 'rechaza-clave') {
            fwrite($conexion, "535 Contraseña rechazada\r\n");
            continue;
        }

        $autenticado = true;
        fwrite($conexion, "235 Autenticación correcta\r\n");
        continue;
    }

    if (str_starts_with($comando, 'EHLO') || str_starts_with($comando, 'HELO')) {
        if ($modo === 'rechaza-ehlo') {
            fwrite($conexion, "554 EHLO rechazado\r\n");
            continue;
        }

        fwrite(
            $conexion,
            "250-alquiler.local\r\n250-AUTH LOGIN PLAIN\r\n250 HELP\r\n"
        );
        continue;
    }

    if ($comando === 'STARTTLS') {
        if ($modo === 'starttls-inesperado') {
            fwrite($conexion, "250 STARTTLS aceptado\r\n");
            continue;
        }

        fwrite($conexion, "502 STARTTLS no disponible\r\n");
        continue;
    }

    if ($comando === 'AUTH LOGIN') {
        if ($modo === 'rechaza-auth') {
            fwrite($conexion, "535 Autenticación rechazada\r\n");
            continue;
        }

        $autenticando = 1;
        fwrite($conexion, "334 VXNlcm5hbWU6\r\n");
        continue;
    }

    if (str_starts_with($comando, 'MAIL FROM')) {
        if ($modo === 'requiere-auth' && !$autenticado) {
            fwrite($conexion, "530 Autenticación requerida\r\n");
            continue;
        }

        if ($modo === 'rechaza-mail') {
            fwrite($conexion, "550 Remitente rechazado\r\n");
            continue;
        }

        fwrite($conexion, "250 OK\r\n");
        continue;
    }

    if (str_starts_with($comando, 'RCPT TO')) {
        if ($modo === 'rechaza-rcpt') {
            fwrite($conexion, "550 Destinatario rechazado\r\n");
            continue;
        }

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

        if ($modo === 'rechaza-fin-data') {
            fwrite($conexion, "554 Mensaje rechazado\r\n");
            continue;
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

$meta = stream_get_meta_data($conexion);

if ($bandera !== '' && empty($meta['timed_out'])) {
    @file_put_contents($bandera . '.cerrado', '1');
}

fclose($conexion);
fclose($servidor);
