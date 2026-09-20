<?php

declare(strict_types=1);

namespace App\Helpers;

class IpHelper
{
    public static function obtener(): ?string
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;

        if (!self::esIpValida($remoteAddr)) {
            return null;
        }

        $trustedProxies = self::obtenerProxiesConfiables();

        // Si la conexión directa no proviene de un proxy confiable,
        // no se debe confiar en X-Forwarded-For.
        if (!self::perteneceAProxyConfiable($remoteAddr, $trustedProxies)) {
            return $remoteAddr;
        }

        $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;

        if (!is_string($forwardedFor) || trim($forwardedFor) === '') {
            return $remoteAddr;
        }

        $ips = array_map(
            'trim',
            explode(',', $forwardedFor)
        );

        // Se recorre desde el último salto hacia atrás.
        // La primera IP válida que no pertenece a un proxy confiable
        // se considera la IP del cliente.
        for ($i = count($ips) - 1; $i >= 0; $i--) {
            $ip = $ips[$i];

            if (!self::esIpValida($ip)) {
                continue;
            }

            if (!self::perteneceAProxyConfiable($ip, $trustedProxies)) {
                return $ip;
            }
        }

        // Si toda la cadena pertenece a proxies confiables,
        // se utiliza REMOTE_ADDR como fallback.
        return $remoteAddr;
    }

    private static function obtenerProxiesConfiables(): array
    {
        $config = $_ENV['TRUSTED_PROXIES'] ?? '';

        if (!is_string($config) || trim($config) === '') {
            return [];
        }

        return array_values(
            array_filter(
                array_map(
                    'trim',
                    explode(',', $config)
                )
            )
        );
    }

    private static function esIpValida(?string $ip): bool
    {
        return is_string($ip)
            && filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    private static function perteneceAProxyConfiable(
        string $ip,
        array $trustedProxies
    ): bool {
        foreach ($trustedProxies as $trustedProxy) {
            if (self::coincideIpOcidr($ip, $trustedProxy)) {
                return true;
            }
        }

        return false;
    }

    private static function coincideIpOcidr(
        string $ip,
        string $red
    ): bool {
        if (!str_contains($red, '/')) {
            $ipBinaria = inet_pton($ip);
            $redBinaria = inet_pton($red);

            return $ipBinaria !== false
                && $redBinaria !== false
                && $ipBinaria === $redBinaria;
        }

        [$subred, $prefijo] = explode('/', $red, 2);

        $subred = trim($subred);
        $prefijo = trim($prefijo);

        if (
            !self::esIpValida($subred)
            || !ctype_digit($prefijo)
        ) {
            return false;
        }

        $ipBinaria = inet_pton($ip);
        $subredBinaria = inet_pton($subred);

        if ($ipBinaria === false || $subredBinaria === false) {
            return false;
        }

        // IPv4 e IPv6 no pueden compararse entre sí.
        if (strlen($ipBinaria) !== strlen($subredBinaria)) {
            return false;
        }

        $bits = (int) $prefijo;
        $maxBits = strlen($ipBinaria) * 8;

        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }

        $bytesCompletos = intdiv($bits, 8);
        $bitsRestantes = $bits % 8;

        if (
            $bytesCompletos > 0
            && substr($ipBinaria, 0, $bytesCompletos)
                !== substr($subredBinaria, 0, $bytesCompletos)
        ) {
            return false;
        }

        if ($bitsRestantes === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $bitsRestantes)) & 0xFF;

        return (
            (ord($ipBinaria[$bytesCompletos]) & $mask)
            ===
            (ord($subredBinaria[$bytesCompletos]) & $mask)
        );
    }
}