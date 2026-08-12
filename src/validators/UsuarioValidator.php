<?php

namespace App\Validators;

class UsuarioValidator
{
    public static function validarUsuario($data, $requerirId = false, $requerirContrasena = true) {
        $errores = [];

        if ($requerirId) {
            $resultado = self::validarIdRequeridoUsuario($data['id'] ?? null, 'usuario');
            if (!$resultado['success']) {
                $errores['id'] = $resultado['error'];
            }
        }

        $resultado = self::validarNombreUsuario($data['nombre'] ?? null);
        if (!$resultado['success']) $errores['nombre'] = $resultado['error'];

        $resultado = self::validarApellidoUsuario($data['apellido'] ?? null);
        if (!$resultado['success']) $errores['apellido'] = $resultado['error'];

        $resultado = self::validarEmailUsuario($data['email'] ?? null);
        if (!$resultado['success']) $errores['email'] = $resultado['error'];

        if (!empty($data['telefono'])) {
            $resultado = self::validarTelefonoUsuario($data['telefono']);
            if (!$resultado['success']) $errores['telefono'] = $resultado['error'];
        }

        if (!empty($data['domicilio'])) {
            $resultado = self::validarDomicilioUsuario($data['domicilio']);
            if (!$resultado['success']) $errores['domicilio'] = $resultado['error'];
        }

        if ($requerirContrasena) {
            $resultado = self::validarContrasenaUsuario($data['contrasena'] ?? null);
            if (!$resultado['success']) $errores['contrasena'] = $resultado['error'];
        }

        $resultado = self::validarRolIdUsuario($data['rol_id'] ?? null);
        if (!$resultado['success']) $errores['rol_id'] = $resultado['error'];

        if (!empty($errores)) {
            return [
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $errores
            ];
        }

        return [
            'success' => true,
            'message' => 'Validación exitosa',
            'errors' => null
        ];
    }

    public static function validarIdRequeridoUsuario($id, $campo = '') {
        if ($id === null || $id === '') {
            return ['success' => false, 'error' => "El ID de $campo es requerido"];
        }
        if (!is_numeric($id)) {
            return ['success' => false, 'error' => "El ID de $campo debe ser numérico"];
        }
        if ($id <= 0) {
            return ['success' => false, 'error' => "El ID de $campo debe ser positivo"];
        }
        return ['success' => true, 'error' => null];
    }

    public static function validarNombreUsuario($nombre) {
        if (!$nombre) return ['success' => false, 'error' => 'El nombre es requerido'];

        $nombre = trim($nombre);

        if (strlen($nombre) < 2)
            return ['success' => false, 'error' => 'El nombre debe tener al menos 2 caracteres'];

        if (strlen($nombre) > 50)
            return ['success' => false, 'error' => 'El nombre no puede exceder los 50 caracteres'];

        if (!preg_match('/^[a-zA-ZáéíóúñÑÁÉÍÓÚ\s]+$/u', $nombre))
            return ['success' => false, 'error' => 'El nombre solo puede contener letras y espacios'];

        return ['success' => true, 'error' => null];
    }

    public static function validarApellidoUsuario($apellido) {
        return self::validarNombreUsuario($apellido);
    }

    public static function validarEmailUsuario($email) {
        if (!$email)
            return ['success' => false, 'error' => 'El email es requerido'];

        if (strlen($email) > 100)
            return ['success' => false, 'error' => 'El email no puede exceder los 100 caracteres'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            return ['success' => false, 'error' => 'El email no es válido'];

        return ['success' => true, 'error' => null];
    }

    public static function validarTelefonoUsuario($telefono) {
        $telefono = preg_replace('/[^0-9]/', '', $telefono);

        if (strlen($telefono) < 6)
            return ['success' => false, 'error' => 'El teléfono debe tener al menos 6 dígitos'];

        if (strlen($telefono) > 15)
            return ['success' => false, 'error' => 'El teléfono no puede exceder los 15 dígitos'];

        return ['success' => true, 'error' => null];
    }

    public static function validarDomicilioUsuario($domicilio) {
        $domicilio = trim($domicilio);

        if (strlen($domicilio) < 5)
            return ['success' => false, 'error' => 'El domicilio debe tener al menos 5 caracteres'];

        if (strlen($domicilio) > 100)
            return ['success' => false, 'error' => 'El domicilio no puede exceder los 100 caracteres'];

        return ['success' => true, 'error' => null];
    }

    public static function validarContrasenaUsuario($contrasena) {
        if (!$contrasena)
            return ['success' => false, 'error' => 'La contraseña es requerida'];

        if (strlen($contrasena) < 6)
            return ['success' => false, 'error' => 'Debe tener al menos 6 caracteres'];

        if (strlen($contrasena) > 255)
            return ['success' => false, 'error' => 'No puede exceder los 255 caracteres'];

        return ['success' => true, 'error' => null];
    }

    public static function validarRolIdUsuario($rolId) {
        if ($rolId === null || $rolId === '')
            return ['success' => false, 'error' => 'El rol es requerido'];

        if (!in_array((int)$rolId, [1, 2, 3]))
            return ['success' => false, 'error' => 'Rol inválido'];

        return ['success' => true, 'error' => null];
    }

    public static function validarCrearUsuario($data) {
        return self::validarUsuario($data, false, true);
    }

    public static function validarActualizarUsuario($data, $requerirContrasena = false) {
        return self::validarUsuario($data, false, $requerirContrasena);
    }

    public static function validarSoloIdUsuario($id) {
        $r = self::validarIdRequeridoUsuario($id, 'usuario');

        if (!$r['success']) {
            return [
                'success' => false,
                'message' => 'ID inválido',
                'errors' => ['id' => $r['error']]
            ];
        }

        return [
            'success' => true,
            'message' => 'ID válido',
            'errors' => null
        ];
    }

    public static function validarEmailLoginUsuario($email) {
        $r = self::validarEmailUsuario($email);

        if (!$r['success']) {
            return [
                'success' => false,
                'message' => 'Email inválido',
                'errors' => ['email' => $r['error']]
            ];
        }

        return [
            'success' => true,
            'message' => 'Email válido',
            'errors' => null
        ];
    }
}