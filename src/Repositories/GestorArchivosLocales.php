<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\BadRequestException;

class GestorArchivosLocales implements GestorArchivosInterface
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

        if (!$this->isWritable($directory)) {
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

        if (!$this->copyFile($rutaTemporal, $destino)) {
            throw new BadRequestException(
                'Error al guardar la imagen procesada'
            );
        }

        clearstatcache(true, $destino);

        if (
            !is_file($destino)
            || $this->fileSize($destino) === false
            || $this->fileSize($destino) <= 0
        ) {
            @unlink($destino);

            throw new BadRequestException(
                'No se pudo verificar la imagen guardada'
            );
        }

        // Genera una versión WebP para optimizar la entrega en el frontend.
        if (function_exists('imagewebp')) {
            $imagen = @imagecreatefromjpeg($destino);

            if ($imagen instanceof \GdImage) {
                $webpPath = preg_replace(
                    '/\.jpg$/i',
                    '.webp',
                    $destino
                );

                @imagewebp($imagen, $webpPath, 80);
                imagedestroy($imagen);
            }
        }

        return $nombreArchivo;
    }

    public function delete(string $path): void
    {
        $rutaFisica = dirname(__DIR__, 2) . '/public' . $path;

        if (!file_exists($rutaFisica)) {
            return;
        }

        if (!$this->deleteFile($rutaFisica)) {
            throw new BadRequestException(
                'No se pudo eliminar la imagen'
            );
        }

        $webpPath = preg_replace('/\.jpg$/i', '.webp', $rutaFisica);

        if (is_string($webpPath) && is_file($webpPath)) {
            @unlink($webpPath);
        }
    }

    protected function isWritable(string $path): bool
    {
        return is_writable($path);
    }

    protected function copyFile(string $source, string $destination): bool
    {
        return @copy($source, $destination);
    }

    protected function fileSize(string $path): int|false
    {
        clearstatcache(true, $path);

        return @filesize($path);
    }

    protected function deleteFile(string $path): bool
    {
        return @unlink($path);
    }
}
