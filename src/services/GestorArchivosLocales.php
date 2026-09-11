<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BadRequestException;

final class GestorArchivosLocales implements GestorArchivosInterface
{
    public function upload(array $file, string $directory): string
    {
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new BadRequestException(
                'No se pudo crear el directorio de imágenes'
            );
        }

        $extension = strtolower(
            pathinfo($file['name'], PATHINFO_EXTENSION)
        );

        $nombreArchivo = time()
            . '_'
            . bin2hex(random_bytes(6))
            . '.'
            . $extension;

        $destino = $directory . '/' . $nombreArchivo;

        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            throw new BadRequestException(
                'Error al guardar la imagen'
            );
        }

        return $nombreArchivo;
    }
}