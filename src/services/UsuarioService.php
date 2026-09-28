<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Rol;
use App\Models\Usuario;
use App\Repositories\UsuarioRepositoryInterface;
use App\Sanitizers\UsuarioSanitizer;
use App\Validators\UsuarioValidator;

class UsuarioService
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $repository,
        private readonly LogActividadService $logActividadService
    ) {
    }

    public function listar(array $filtros = []): array
    {
        $usuarios = $this->repository->all($filtros);

        return [
            'items' => $usuarios,
            'total' => $usuarios->count(),
        ];
    }

    public function obtener($rawId): Usuario
    {
        $id = UsuarioSanitizer::sanitizarIdUsuario($rawId);

        $validacion = UsuarioValidator::validarSoloIdUsuario($id);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $usuario = $this->repository->findById($id);

        if (!$usuario) {
            throw new NotFoundException(
                'Usuario no encontrado'
            );
        }

        return $usuario;
    }

    public function obtenerConRol($rawId): Usuario
    {
        $id = UsuarioSanitizer::sanitizarIdUsuario($rawId);

        $validacion = UsuarioValidator::validarSoloIdUsuario($id);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $usuario = $this->repository->findByIdWithRole($id);

        if (!$usuario) {
            throw new NotFoundException(
                'Usuario no encontrado'
            );
        }

        return $usuario;
    }

    public function actualizar(
        $rawId,
        array $rawData
    ): void {
        $usuario = $this->obtener($rawId);

        if ($rawData === []) {
            throw new BadRequestException(
                'Debe enviar al menos un campo para actualizar'
            );
        }

        unset(
            $rawData['id'],
            $rawData['deleted_at'],
            $rawData['rol_id']
        );

        $camposPermitidos = [
            'nombre',
            'apellido',
            'email',
            'telefono',
            'domicilio',
            'contrasena',
        ];

        $datosRecibidos = array_intersect_key(
            $rawData,
            array_flip($camposPermitidos)
        );

        if ($datosRecibidos === []) {
            throw new BadRequestException(
                'No se enviaron campos actualizables'
            );
        }

        $data = UsuarioSanitizer::sanitizarActualizacion(
            $datosRecibidos
        );

        $validacion = UsuarioValidator::validarActualizacionParcial(
            $data
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        if (
            array_key_exists('email', $data)
            && $this->repository->existsByEmail(
                $data['email'],
                $usuario->id
            )
        ) {
            throw new ConflictException(
                'El email ya está registrado'
            );
        }

        if (array_key_exists('contrasena', $data)) {
            $data['contrasena'] = password_hash(
                $data['contrasena'],
                PASSWORD_DEFAULT
            );
        }

        $this->repository->update(
            $usuario,
            $data
        );

        $this->logActividadService->registrar(
            $usuario->id,
            'Actualización de usuario'
        );
    }

    public function crearDesdeAdmin(
        array $rawData,
        int $adminId
    ): Usuario {
        $data = UsuarioSanitizer::sanitizarUsuario($rawData);

        $rolId = UsuarioSanitizer::sanitizarRolId(
            $rawData['rol_id'] ?? null
        );

        $validacion = UsuarioValidator::validarRegistro($data);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        if (
            !in_array(
                (int) $rolId,
                [Rol::USUARIO, Rol::ADMIN],
                true
            )
        ) {
            throw new ValidationException([
                'rol_id' => [
                    'El rol debe ser usuario o administrador',
                ],
            ]);
        }

        if ($this->repository->existsByEmail($data['email'])) {
            throw new ConflictException(
                'El email ya está registrado'
            );
        }

        $data['contrasena'] = password_hash(
            $data['contrasena'],
            PASSWORD_DEFAULT
        );

        $usuario = $this->repository->createWithRole(
            $data,
            (int) $rolId
        );

        $this->logActividadService->registrar(
            $adminId,
            'Alta de usuario desde administración'
        );

        return $usuario;
    }

    public function eliminar($rawId): void
    {
        $usuario = $this->obtener($rawId);

        $this->repository->delete($usuario);

        $this->logActividadService->registrar(
            $usuario->id,
            'Eliminación de usuario'
        );
    }

    public function restaurar($rawId): void
    {
        $id = UsuarioSanitizer::sanitizarIdUsuario($rawId);

        $validacion = UsuarioValidator::validarSoloIdUsuario($id);

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $usuario = $this->repository->findDeletedById($id);

        if (!$usuario) {
            throw new NotFoundException(
                'Usuario eliminado no encontrado'
            );
        }

        if ($this->repository->existsByEmail($usuario->email)) {
            throw new ConflictException(
                'Ya existe un usuario activo con ese email'
            );
        }

        $this->repository->restore($usuario);

        $this->logActividadService->registrar(
            $usuario->id,
            'Restauración de usuario'
        );
    }
}