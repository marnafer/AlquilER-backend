<?php

namespace App\Validators;

final class ImageUploadValidator implements ImageUploadValidatorInterface
{
    public function validate(array $file): ?string
    {
        if (
            !$file ||
            !isset($file['tmp_name']) ||
            !is_uploaded_file($file['tmp_name'])
        ) {
            return 'Debe enviar una imagen';
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            return 'La imagen supera 5MB';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ], true)) {
            return 'Formato no permitido';
        }

        return null;
    }
}