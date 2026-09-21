<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BadRequestException;

final class GestorArchivosLocales implements GestorArchivosInterface
{
    public function upload(array $file, string $directory): string
    {
        if (
            !is_dir($directory)
            && !@mkdir($directory, 0755, true)
            && !is_dir($directory)
        ) {
            throw new BadRequestException(
                'No se pudo crear el directorio de imágenes'
            );
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            throw new BadRequestException(
                'No se pudo determinar el formato de la imagen'
            );
        }

        $mime = finfo_file($finfo, $file['tmp_name']);

        finfo_close($finfo);

        $extensiones = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];

        if ($mime === false || !isset($extensiones[$mime])) {
            throw new BadRequestException(
                'Formato de imagen no permitido'
            );
        }

        $extension = $extensiones[$mime];

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