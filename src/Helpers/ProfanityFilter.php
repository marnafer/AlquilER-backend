<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Filtro de lenguaje inapropiado (groserias, insultos, discriminacion).
 *
 * Se usa como validacion (rechazar) y/o sanitizacion (enmascarar).
 * La lista es extensible; se puede mover a un archivo de configuracion
 * o a la base de datos si se requiere gestion dinamica.
 */
class ProfanityFilter
{
    /**
     * Lista base de terminos inapropiados (espanol + ingles).
     * Se comparan de forma normalizada (minusculas, sin acentos, sin separadores).
     */
    private static array $terminos = [
        // Espanol
        'puta', 'puto', 'mierda', 'carajo', 'joder', 'coño', 'cono', 'pendejo', 'pendeja',
        'idiota', 'imbecil', 'estupido', 'estupida', 'boludo', 'boluda', 'pelotudo', 'pelotuda',
        'forro', 'forra', 'trolo', 'trolo', 'maricon', 'marica', 'culiado', 'culiada',
        'hijo de puta', 'hijodeputa', 'la concha', 'concha de tu madre', 'maldito', 'maldita',
        'basura', 'asqueroso', 'asquerosa', 'inutil', 'tarado', 'tarada', 'pelotudo',
        'chingar', 'chingada', 'verga', 'cabron', 'cabrona', 'pinche', 'güey', 'guey',
        'zorra', 'zorro', 'prostituta', 'prostituto', 'maricón', 'joto', 'culero', 'culera',
        'pendejada', 'mamada', 'mamar', 'chingar', 'chingo', 'chingas', 'chingues',
        // Ingles
        'fuck', 'shit', 'bitch', 'asshole', 'bastard', 'dick', 'pussy', 'cunt', 'whore',
        'slut', 'nigger', 'nigga', 'faggot', 'retard', 'retarded', 'douche', 'douchebag',
        'motherfucker', 'bullshit', 'crap', 'damn', 'hell', 'suck', 'sucks',
    ];

    /**
     * Normaliza un texto para comparacion:
     * minusculas, sin acentos, sin separadores, leetspeak basico.
     */
    public static function normalizar(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');

        // Quitar acentos
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
        ]);

        // Leetspeak basico
        $texto = strtr($texto, [
            '0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's',
            '7' => 't', '8' => 'b', '@' => 'a', '$' => 's', '!' => 'i',
        ]);

        // Eliminar todo lo que no sea letra o digito (espacios, puntuacion, simbolos)
        $texto = preg_replace('/[^a-z0-9]/u', '', $texto);

        return $texto;
    }

    /**
     * Normaliza la lista de terminos una vez.
     */
    private static ?array $terminosNormalizados = null;

    private static function terminosNormalizados(): array
    {
        if (self::$terminosNormalizados === null) {
            $norm = [];
            foreach (self::$terminos as $t) {
                $n = self::normalizar($t);
                if ($n !== '') {
                    $norm[] = $n;
                }
            }
            // unicos
            self::$terminosNormalizados = array_values(array_unique($norm));
        }
        return self::$terminosNormalizados;
    }

    /**
     * Indica si el texto contiene lenguaje inapropiado.
     */
    public static function contiene(string $texto): bool
    {
        $norm = self::normalizar($texto);
        if ($norm === '') {
            return false;
        }

        foreach (self::terminosNormalizados() as $termino) {
            if (str_contains($norm, $termino)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Enmascara terminos inapropiados con asteriscos (opcional).
     * Devuelve el texto original si no hay coincidencias.
     */
    public static function enmascarar(string $texto): string
    {
        // Implementacion simple: si contiene, reemplaza la palabra normalizada
        // por asteriscos manteniendo longitud. Para uso avanzado, usar regex por palabra.
        if (!self::contiene($texto)) {
            return $texto;
        }

        // Fallback: devolver texto con marcador
        return '[contenido inapropiado]';
    }

    /**
     * Agrega terminos a la lista en tiempo de ejecucion (opcional).
     */
    public static function agregarTerminos(array $nuevos): void
    {
        foreach ($nuevos as $t) {
            if (is_string($t) && trim($t) !== '') {
                self::$terminos[] = $t;
            }
        }
        self::$terminosNormalizados = null; // invalidar cache
    }
}
