<?php

namespace App\Sanitizers;

class UsuarioSanitizer
{
    public static function sanitizarUsuario($data) {
        return [
            'id' => self::sanitizarIdUsuario($data['id'] ?? null),
            'nombre' => self::sanitizarNombre($data['nombre'] ?? null),
            'apellido' => self::sanitizarApellido($data['apellido'] ?? null),
            'email' => self::sanitizarEmail($data['email'] ?? null),
            'telefono' => self::sanitizarTelefono($data['telefono'] ?? null),
            'domicilio' => self::sanitizarDomicilio($data['domicilio'] ?? null),
            'contrasena' => $data['contrasena'] ?? null,
            'rol_id' => self::sanitizarRolId($data['rol_id'] ?? null),
            'deleted_at' => self::sanitizarFechaEliminacion($data['deleted_at'] ?? null)
        ];
    }

    public static function sanitizarIdUsuario($id) {
        if ($id === null || $id === '') return null;
        $id = filter_var($id, FILTER_VALIDATE_INT);
        return ($id !== false && $id > 0) ? $id : null;
    }

    public static function sanitizarNombre($nombre) {
        if (!$nombre) return null;
        $nombre = trim($nombre);
        $nombre = preg_replace('/\s+/', ' ', $nombre);
        $nombre = ucwords(strtolower($nombre));
        $nombre = preg_replace('/[^a-zA-ZáéíóúñÑÁÉÍÓÚ\s]/u', '', $nombre);
        return substr(htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'), 0, 50);
    }

    public static function sanitizarApellido($apellido) {
        if (!$apellido) return null;
        $apellido = trim($apellido);
        $apellido = preg_replace('/\s+/', ' ', $apellido);
        $apellido = ucwords(strtolower($apellido));
        $apellido = preg_replace('/[^a-zA-ZáéíóúñÑÁÉÍÓÚ\s]/u', '', $apellido);
        return substr(htmlspecialchars($apellido, ENT_QUOTES, 'UTF-8'), 0, 50);
    }

    public static function sanitizarEmail($email) {
        if (!$email) return null;
        $email = strtolower(trim($email));
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return substr(htmlspecialchars($email, ENT_QUOTES, 'UTF-8'), 0, 100);
    }

    public static function sanitizarTelefono($telefono) {
        if (!$telefono) return null;
        $telefono = preg_replace('/[^0-9+\-\s\(\)]/', '', $telefono);
        return substr(htmlspecialchars(trim($telefono), ENT_QUOTES, 'UTF-8'), 0, 25);
    }

    public static function sanitizarDomicilio($domicilio) {
        if (!$domicilio) return null;
        $domicilio = preg_replace('/\s+/', ' ', trim($domicilio));
        return substr(htmlspecialchars($domicilio, ENT_QUOTES, 'UTF-8'), 0, 100);
    }

    public static function sanitizarRolId($rolId) {
        if ($rolId === null || $rolId === '') return null;
        $rolId = filter_var($rolId, FILTER_VALIDATE_INT);
        return ($rolId !== false && $rolId > 0) ? $rolId : null;
    }

    public static function sanitizarFechaEliminacion($fecha) {
        if (!$fecha) return null;
        $timestamp = strtotime($fecha);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }

    public static function sanitizarSoloEmail($email) {
        return self::sanitizarEmail($email);
    }

    public static function sanitizarSoloContrasena($contrasena) {
        return $contrasena ?: null;
    }
}