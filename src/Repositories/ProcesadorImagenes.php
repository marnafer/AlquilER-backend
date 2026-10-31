<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\BadRequestException;
use GdImage;
use Throwable;

final class ProcesadorImagenes implements ProcesadorImagenesInterface
{
    private const MAX_DIMENSION = 12000;
    private const MAX_PIXELES = 40_000_000;
    private const MAX_LADO_SALIDA = 1920;
    private const CALIDAD_JPEG = 80;

    public function procesar(array $file): string
    {
        $rutaOriginal = $file['tmp_name'] ?? null;

        if (
            !is_string($rutaOriginal)
            || $rutaOriginal === ''
            || !is_file($rutaOriginal)
            || !is_readable($rutaOriginal)
        ) {
            throw new BadRequestException(
                'No se puede leer la imagen recibida'
            );
        }

        $informacion = @getimagesize($rutaOriginal);

        if (
            $informacion === false
            || !isset($informacion[0], $informacion[1], $informacion['mime'])
        ) {
            throw new BadRequestException(
                'El archivo no contiene una imagen válida'
            );
        }

        $ancho = $informacion[0];
        $alto = $informacion[1];
        $mime = $informacion['mime'];

        if (
            !is_int($ancho)
            || !is_int($alto)
            || $ancho <= 0
            || $alto <= 0
            || $ancho > self::MAX_DIMENSION
            || $alto > self::MAX_DIMENSION
            || $ancho > intdiv(self::MAX_PIXELES, $alto)
        ) {
            throw new BadRequestException(
                'Las dimensiones de la imagen son demasiado grandes'
            );
        }

        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
            throw new BadRequestException(
                'Formato no permitido. Solo se permiten imágenes JPEG y PNG'
            );
        }

        if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatefrompng')) {
            throw new BadRequestException(
                'El servidor no tiene habilitado el procesamiento de imágenes'
            );
        }

        $imagenOriginal = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($rutaOriginal),
            'image/png' => @imagecreatefrompng($rutaOriginal),
        };

        if (!$imagenOriginal instanceof GdImage) {
            throw new BadRequestException(
                'No se pudo procesar la imagen recibida'
            );
        }

        $imagenSalida = null;
        $rutaTemporal = null;

        try {
            // Conserva la proporción y nunca amplía la imagen.
            $escala = min(
                1,
                self::MAX_LADO_SALIDA / max($ancho, $alto)
            );

            $nuevoAncho = max(1, (int) round($ancho * $escala));
            $nuevoAlto = max(1, (int) round($alto * $escala));

            $imagenSalida = imagecreatetruecolor(
                $nuevoAncho,
                $nuevoAlto
            );

            if (!$imagenSalida instanceof GdImage) {
                throw new BadRequestException(
                    'No se pudo preparar la imagen optimizada'
                );
            }

            // JPEG no admite transparencia: se utiliza fondo blanco.
            $blanco = imagecolorallocate($imagenSalida, 255, 255, 255);
            imagefill($imagenSalida, 0, 0, $blanco);

            if (!imagecopyresampled(
                $imagenSalida,
                $imagenOriginal,
                0,
                0,
                0,
                0,
                $nuevoAncho,
                $nuevoAlto,
                $ancho,
                $alto
            )) {
                throw new BadRequestException(
                    'No se pudo redimensionar la imagen'
                );
            }

            $rutaTemporal = tempnam(sys_get_temp_dir(), 'alquiler_img_');

            if ($rutaTemporal === false) {
                throw new BadRequestException(
                    'No se pudo crear el archivo temporal de la imagen'
                );
            }

            if (!imagejpeg(
                $imagenSalida,
                $rutaTemporal,
                self::CALIDAD_JPEG
            )) {
                throw new BadRequestException(
                    'No se pudo generar la imagen optimizada'
                );
            }

            clearstatcache(true, $rutaTemporal);

            if (
                !is_file($rutaTemporal)
                || filesize($rutaTemporal) === false
                || filesize($rutaTemporal) <= 0
            ) {
                throw new BadRequestException(
                    'La imagen optimizada está vacía o es inválida'
                );
            }

            return $rutaTemporal;
        } catch (Throwable $e) {
            if ($rutaTemporal !== null && is_file($rutaTemporal)) {
                @unlink($rutaTemporal);
            }

            throw $e;
        } finally {
            imagedestroy($imagenOriginal);

            if ($imagenSalida instanceof GdImage) {
                imagedestroy($imagenSalida);
            }
        }
    }
}