<?php

namespace App\Controllers\Api;

use App\Models\Usuario;
use App\Helpers\JwtHelper;
use App\Helpers\Response;
use App\Middlewares\AutenticadorMiddleware;
use App\Sanitizers\UsuarioSanitizer;
use App\Validators\UsuarioValidator;


class UsuarioController
{
    /**
     * SOLO ADMIN
     * GET /api/usuarios
     */
    public function listarUsuariosApi()
    {
        $user = AutenticadorMiddleware::verificar();

        if ($user->rol_id != 3) {
            if ($user->rol_id != 3) {
                Response::unauthorized('Solo administradores');
            }
        }

        try {
            $usuarios = Usuario::all();

            Response::success([
                'items' => $usuarios,
                'total' => $usuarios->count()
            ]);

        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/usuarios/{id}
     */
    public function mostrar($id)
    {
        $user = AutenticadorMiddleware::verificar();

        $idSan = (int)$id;

        if ($user->rol_id != 3 && $user->sub != $idSan) {
            Response::unauthorized('No autorizado');
        }

        try {
            $usuario = Usuario::find($idSan);

            if (!$usuario) {
                Response::notFound('Usuario no encontrado');
            }

            Response::success($usuario);

        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * DELETE /api/usuarios/{id}
     */
    public function eliminar($id)
    {
        $user = AutenticadorMiddleware::verificar();

        if ($user->rol_id != 3) {
            Response::unauthorized('Solo administradores');
        }

        try {

            $usuario = Usuario::find((int)$id);

            if (!$usuario) {
                Response::notFound('Usuario no encontrado');
            }

            $usuario->delete();

            Response::success([], 200, 'Usuario eliminado');

        } catch (\Exception $e) {

            Response::serverError($e->getMessage());

        }
    }

    /**
     * PUT /api/usuarios/{id}
     */
    public function actualizar($id)
    {
        $user = AutenticadorMiddleware::verificar();

        $id = (int)$id;

        if ($user->rol_id != 3 && $user->sub != $id) {
            if ($user->rol_id != 3 && $user->sub != $id) {
                Response::unauthorized('No autorizado');
            }
        }

        $raw = json_decode(file_get_contents('php://input'), true);

        if (!is_array($raw)) {
            Response::badRequest('Datos inválidos');
        }

        $data = UsuarioSanitizer::sanitizarUsuario($raw);
        $validacion = UsuarioValidator::validarActualizarUsuario($data);

        if (!$validacion['success']) {
            if (!$validacion['success']) {
                Response::validationError($validacion['errors']);
            }
        }

        try {
            $usuario = Usuario::find($id);

            if (!$usuario) {
                if (!$usuario) {
                    Response::notFound('Usuario no encontrado');
                }
            }

            if (!empty($data['contrasena'])) {
                $data['contrasena'] = password_hash($data['contrasena'], PASSWORD_DEFAULT);
            }

            $usuario->update($data);

            Response::success([], 200, 'Usuario actualizado correctamente');

        } catch (\Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    public function restaurar($id)
    {
        $user = AutenticadorMiddleware::soloAdmin();

        try {

            $usuario = Usuario::onlyTrashed()->find($id);

            if (!$usuario) {
                Response::notFound('Usuario eliminado no encontrado');
            }

            $usuario->restore();

            Response::success(
                [],
                200,
                'Usuario restaurado correctamente'
            );

        } catch (\Exception $e) {

            Response::serverError($e->getMessage());

        }
    }

    public function perfil()
    {
        $user = AutenticadorMiddleware::verificar();

        $usuario = Usuario::find($user->sub);

        if (!$usuario) {
            Response::notFound('Usuario no encontrado');
        }

        $rolNombre = match ((int)$usuario->rol_id) {
            3 => 'Administrador',
            2 => 'Propietario',
            1 => 'Usuario',
            default => 'Desconocido'
        };

        Response::success([
            'id' => $usuario->id,
            'nombre' => $usuario->nombre,
            'apellido' => $usuario->apellido,
            'email' => $usuario->email, 
            'telefono' => $usuario->telefono,
            'domicilio' => $usuario->domicilio,
            'rol_id' => $usuario->rol_id,
            'rol' => $rolNombre
        ]);
    }
}