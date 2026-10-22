<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\BadRequestException;

final class GestorArchivosLocales implements GestorArchivosInterface
{
    public function upload(string $rutaTemporal, string $directory): string
    {
        if (
            $rutaTemporal === ''
            || !is_file($rutaTemporal)
            || !is_readable($rutaTemporal)
        ) {
            throw new BadRequestException(
                'No se puede leer la imagen procesada'
            );
        }

        if (!is_dir($directory)) {
            if (!@mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new BadRequestException(
                    'No se pudo crear el directorio de imágenes'
                );
            }
        }

        if (!is_writable($directory)) {
            throw new BadRequestException(
                'No se puede escribir en el directorio de imágenes'
            );
        }

        // El procesador debe entregar un JPEG válido.
        $informacion = @getimagesize($rutaTemporal);

        if (
            $informacion === false
            || ($informacion['mime'] ?? null) !== 'image/jpeg'
        ) {
            throw new BadRequestException(
                'El archivo procesado no es un JPEG válido'
            );
        }

        $nombreArchivo = bin2hex(random_bytes(16)) . '.jpg';
        $destino = rtrim($directory, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $nombreArchivo;

        if (!@copy($rutaTemporal, $destino)) {
            throw new BadRequestException(
                'Error al guardar la imagen procesada'
            );
        }

        clearstatcache(true, $destino);

        if (
            !is_file($destino)
            || filesize($destino) === false
            || filesize($destino) <= 0
        ) {
            @unlink($destino);

            throw new BadRequestException(
                'No se pudo verificar la imagen guardada'
            );
        }

        return $nombreArchivo;
    }

    public function delete(string $path): void
    {
        $rutaFisica = dirname(__DIR__, 2) . '/public' . $path;

        if (!file_exists($rutaFisica)) {
            return;
        }

        if (!unlink($rutaFisica)) {
            throw new BadRequestException(
                'No se pudo eliminar la imagen'
            );
        }
    }
}
