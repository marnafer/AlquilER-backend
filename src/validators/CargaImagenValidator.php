<?php

declare(strict_types=1);

namespace App\Validators;

final class CargaImagenValidator implements CargaImagenValidatorInterface
{
    public function validate(array $file): ?string
    {
        if (
            empty($file)
            || !isset($file['tmp_name'])
            || !is_string($file['tmp_name'])
            || !is_uploaded_file($file['tmp_name'])
        ) {
            return 'Debe enviar una imagen';
        }

        if (
            !isset($file['error'])
            || $file['error'] !== UPLOAD_ERR_OK
        ) {
            return 'No se pudo cargar la imagen';
        }

        if (
            !isset($file['size'])
            || !is_numeric($file['size'])
        ) {
            return 'No se pudo determinar el tamaño de la imagen';
        }

        if ((int) $file['size'] > 5 * 1024 * 1024) {
            return 'La imagen supera 5MB';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            return 'No se pudo determinar el formato de la imagen';
        }

        $mime = finfo_file($finfo, $file['tmp_name']);

        finfo_close($finfo);

        if ($mime === false) {
            return 'No se pudo determinar el formato de la imagen';
        }

        if (!in_array($mime, [
            'image/jpeg',
            'image/png',
        ], true)) {
            return 'Formato no permitido. Solo se permiten imágenes JPEG y PNG';
        }

        return null;
    }
}