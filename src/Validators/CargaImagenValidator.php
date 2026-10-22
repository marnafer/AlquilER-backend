<?php

declare(strict_types=1);

namespace App\Validators;

final class CargaImagenValidator implements CargaImagenValidatorInterface
{
    private const MAX_TAMANO_BYTES = 15 * 1024 * 1024;
    private const MAX_DIMENSION = 12000;
    private const MAX_PIXELES = 40_000_000;

    private const FORMATOS_PERMITIDOS = [
        'image/jpeg',
        'image/png',
    ];

    public function validate(array $file): ?string
    {
        if (empty($file)) {
            return 'Debe enviar una imagen';
        }

        if (
            !isset($file['error'])
            || $file['error'] !== UPLOAD_ERR_OK
        ) {
            return 'No se pudo cargar la imagen';
        }

        if (
            !isset($file['tmp_name'])
            || !is_string($file['tmp_name'])
            || $file['tmp_name'] === ''
            || !is_uploaded_file($file['tmp_name'])
        ) {
            return 'Debe enviar una imagen';
        }

        if (
            !isset($file['size'])
            || !is_numeric($file['size'])
            || (float) $file['size'] < 0
        ) {
            return 'No se pudo determinar el tamaño de la imagen';
        }

        if ((float) $file['size'] > self::MAX_TAMANO_BYTES) {
            return 'La imagen supera 15 MB';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            return 'No se pudo determinar el formato de la imagen';
        }

        try {
            $mime = finfo_file($finfo, $file['tmp_name']);
        } finally {
            finfo_close($finfo);
        }

        if ($mime === false) {
            return 'No se pudo determinar el formato de la imagen';
        }

        if (!in_array($mime, self::FORMATOS_PERMITIDOS, true)) {
            return 'Formato no permitido. Solo se permiten imágenes JPEG y PNG';
        }

        $dimensiones = @getimagesize($file['tmp_name']);

        if (
            $dimensiones === false
            || !isset($dimensiones[0], $dimensiones[1])
            || !is_int($dimensiones[0])
            || !is_int($dimensiones[1])
            || $dimensiones[0] <= 0
            || $dimensiones[1] <= 0
        ) {
            return 'El archivo no contiene una imagen válida';
        }

        [$ancho, $alto] = $dimensiones;

        if (
            $ancho > self::MAX_DIMENSION
            || $alto > self::MAX_DIMENSION
            || $ancho > intdiv(self::MAX_PIXELES, $alto)
        ) {
            return 'Las dimensiones de la imagen son demasiado grandes';
        }

        return null;
    }
}
