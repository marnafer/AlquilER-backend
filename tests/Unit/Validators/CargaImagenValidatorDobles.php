<?php

declare(strict_types=1);

namespace Tests\Unit\Validators {
    final class CargaImagenValidatorDobles
    {
        public static bool $forzarArchivoSubido = false;

        public static bool $fallaAperturaDeFinfo = false;

        public static bool $fallaLecturaDeFinfo = false;

        public static ?string $mimeForzado = null;

        public static function reiniciar(): void
        {
            self::$forzarArchivoSubido = false;
            self::$fallaAperturaDeFinfo = false;
            self::$fallaLecturaDeFinfo = false;
            self::$mimeForzado = null;
        }
    }
}

namespace App\Validators {
    use Tests\Unit\Validators\CargaImagenValidatorDobles;

    /*
     * Dobles de funciones globales para poder ejercitar las ramas de
     * CargaImagenValidator que dependen de la SAPI de subida (is_uploaded_file)
     * y de finfo. Por defecto DELEGAN a la funcion global real, de modo que
     * cargar este archivo no altera el comportamiento de ningun otro test.
     */

    function is_uploaded_file(string $filename): bool
    {
        if (CargaImagenValidatorDobles::$forzarArchivoSubido) {
            return true;
        }

        return \is_uploaded_file($filename);
    }

    function finfo_open(
        int $flags = FILEINFO_NONE,
        ?string $magic_database = null
    ) {
        if (CargaImagenValidatorDobles::$fallaAperturaDeFinfo) {
            return false;
        }

        if ($magic_database === null) {
            return \finfo_open($flags);
        }

        return \finfo_open($flags, $magic_database);
    }

    function finfo_file(
        $finfo,
        string $filename,
        int $flags = 0,
        $context = null
    ) {
        if (CargaImagenValidatorDobles::$fallaLecturaDeFinfo) {
            return false;
        }

        if (CargaImagenValidatorDobles::$mimeForzado !== null) {
            return CargaImagenValidatorDobles::$mimeForzado;
        }

        return \finfo_file($finfo, $filename, $flags, $context);
    }
}
