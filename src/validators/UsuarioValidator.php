<?php

namespace App\Validators;

class UsuarioValidator
{
    /**
     * Valida ID de usuario.
     */
    public static function validarIdUsuario($id): array
    {
        if ($id === null || $id === '') {
            return [
                'success' => false,
                'error' => 'El ID de usuario es requerido'
            ];
        }

        if (!is_numeric($id)) {
            return [
                'success' => false,
                'error' => 'El ID de usuario debe ser numérico'
            ];
        }

        if ((int) $id <= 0) {
            return [
                'success' => false,
                'error' => 'El ID de usuario debe ser positivo'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida nombre.
     */
    public static function validarNombre(?string $nombre): array
    {
        if ($nombre === null || $nombre === '') {
            return [
                'success' => false,
                'error' => 'El nombre es requerido'
            ];
        }

        $nombre = trim($nombre);
        $len = mb_strlen($nombre);

        if ($len < 2) {
            return [
                'success' => false,
                'error' => 'El nombre debe tener al menos 2 caracteres'
            ];
        }

        if ($len > 50) {
            return [
                'success' => false,
                'error' => 'El nombre no puede exceder los 50 caracteres'
            ];
        }

        if (!preg_match(
            '/^[\p{L}\s]+$/u',
            $nombre
        )) {
            return [
                'success' => false,
                'error' => 'El nombre solo puede contener letras y espacios'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida apellido.
     */
    public static function validarApellido(?string $apellido): array
    {
        if ($apellido === null || $apellido === '') {
            return [
                'success' => false,
                'error' => 'El apellido es requerido'
            ];
        }

        $apellido = trim($apellido);
        $len = mb_strlen($apellido);

        if ($len < 2) {
            return [
                'success' => false,
                'error' => 'El apellido debe tener al menos 2 caracteres'
            ];
        }

        if ($len > 50) {
            return [
                'success' => false,
                'error' => 'El apellido no puede exceder los 50 caracteres'
            ];
        }

        if (!preg_match(
            '/^[\p{L}\s]+$/u',
            $apellido
        )) {
            return [
                'success' => false,
                'error' => 'El apellido solo puede contener letras y espacios'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida email.
     */
    public static function validarEmail(?string $email): array
    {
        if ($email === null || $email === '') {
            return [
                'success' => false,
                'error' => 'El email es requerido'
            ];
        }

        if (mb_strlen($email) > 100) {
            return [
                'success' => false,
                'error' => 'El email no puede exceder los 100 caracteres'
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'error' => 'El email no es válido'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida teléfono.
     */
    public static function validarTelefono(?string $telefono): array
    {
        if ($telefono === null || $telefono === '') {
            return [
                'success' => false,
                'error' => 'El teléfono es requerido'
            ];
        }

        if (!is_string($telefono)) {
            return [
                'success' => false,
                'error' => 'El teléfono no es válido'
            ];
        }

        $digitos = preg_replace(
            '/[^0-9]/',
            '',
            $telefono
        );

        $cantidad = strlen($digitos);

        if ($cantidad < 6) {
            return [
                'success' => false,
                'error' => 'El teléfono debe tener al menos 6 dígitos'
            ];
        }

        if ($cantidad > 15) {
            return [
                'success' => false,
                'error' => 'El teléfono no puede exceder los 15 dígitos'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida domicilio.
     */
    public static function validarDomicilio(?string $domicilio): array
    {
        if ($domicilio === null || $domicilio === '') {
            return [
                'success' => false,
                'error' => 'El domicilio es requerido'
            ];
        }

        if (!is_string($domicilio)) {
            return [
                'success' => false,
                'error' => 'El domicilio no es válido'
            ];
        }

        $domicilio = trim($domicilio);
        $len = mb_strlen($domicilio);

        if ($len < 5) {
            return [
                'success' => false,
                'error' => 'El domicilio debe tener al menos 5 caracteres'
            ];
        }

        if ($len > 100) {
            return [
                'success' => false,
                'error' => 'El domicilio no puede exceder los 100 caracteres'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida contraseña.
     */
    public static function validarContrasena(
        ?string $contrasena
    ): array {
        if ($contrasena === null || $contrasena === '') {
            return [
                'success' => false,
                'error' => 'La contraseña es requerida'
            ];
        }

        $len = mb_strlen($contrasena);

        if ($len < 6) {
            return [
                'success' => false,
                'error' => 'La contraseña debe tener al menos 6 caracteres'
            ];
        }

        if ($len > 255) {
            return [
                'success' => false,
                'error' => 'La contraseña no puede exceder los 255 caracteres'
            ];
        }

        return [
            'success' => true,
            'error' => null
        ];
    }

    /**
     * Valida registro completo.
     */
    public static function validarRegistro(array $data): array
    {
        $errores = [];

        $validaciones = [
            'nombre' => 'validarNombre',
            'apellido' => 'validarApellido',
            'email' => 'validarEmail',
            'telefono' => 'validarTelefono',
            'domicilio' => 'validarDomicilio',
            'contrasena' => 'validarContrasena',
        ];

        foreach ($validaciones as $campo => $metodo) {
            $resultado = self::$metodo(
                $data[$campo] ?? null
            );

            if (!$resultado['success']) {
                $errores[$campo] = $resultado['error'];
            }
        }

        return [
            'success' => empty($errores),
            'errors' => $errores
        ];
    }

    /**
     * Valida actualización parcial.
     */
    public static function validarActualizacionParcial(
        array $data
    ): array {
        $errores = [];

        $validaciones = [
            'nombre' => 'validarNombre',
            'apellido' => 'validarApellido',
            'email' => 'validarEmail',
            'telefono' => 'validarTelefono',
            'domicilio' => 'validarDomicilio',
            'contrasena' => 'validarContrasena',
        ];

        foreach ($validaciones as $campo => $metodo) {
            if (!array_key_exists($campo, $data)) {
                continue;
            }

            $resultado = self::$metodo($data[$campo]);

            if (!$resultado['success']) {
                $errores[$campo] = $resultado['error'];
            }
        }

        return [
            'success' => empty($errores),
            'errors' => $errores
        ];
    }

    /**
     * Valida únicamente un ID.
     */
    public static function validarSoloIdUsuario($id): array
    {
        $resultado = self::validarIdUsuario($id);

        if (!$resultado['success']) {
            return [
                'success' => false,
                'message' => 'ID inválido',
                'errors' => [
                    'id' => $resultado['error']
                ]
            ];
        }

        return [
            'success' => true,
            'message' => 'ID válido',
            'errors' => null
        ];
    }

    /**
     * Valida email utilizado durante el login.
     */
    public static function validarEmailLoginUsuario(
        ?string $email
    ): array {
        $resultado = self::validarEmail($email);

        if (!$resultado['success']) {
            return [
                'success' => false,
                'message' => 'Email inválido',
                'errors' => [
                    'email' => $resultado['error']
                ]
            ];
        }

        return [
            'success' => true,
            'message' => 'Email válido',
            'errors' => null
        ];
    }
}