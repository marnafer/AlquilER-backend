<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Exceptions\BadRequestException;
use App\Models\Usuario;
use App\Repositories\UsuarioRepositoryInterface;
use App\Sanitizers\UsuarioSanitizer;
use App\Validators\UsuarioValidator;

class UsuarioService
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $repository
    ) {
    }

    public function listar(): array
    {
        $usuarios = $this->repository->all();

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
            throw new ValidationException([
                'id' => [$validacion['error']]
            ]);
        }

        $usuario = $this->repository->findById($id);

        if (!$usuario) {
            throw new NotFoundException('Usuario no encontrado');
        }

        return $usuario;
    }

    public function eliminar($rawId): void
    {
        $id = UsuarioSanitizer::sanitizarIdUsuario($rawId);

        $validacion = UsuarioValidator::validarSoloIdUsuario($id);

        if (!$validacion['success']) {
            throw new ValidationException([
                'id' => [$validacion['error']]
            ]);
        }

        $usuario = $this->repository->findById($id);

        if (!$usuario) {
            throw new NotFoundException('Usuario no encontrado');
        }

        $this->repository->delete($usuario);
    }

    public function actualizar($rawId, array $rawData): void
    {
        $id = UsuarioSanitizer::sanitizarIdUsuario($rawId);

        $validacion = UsuarioValidator::validarSoloIdUsuario($id);

        if (!$validacion['success']) {
            throw new ValidationException([
                'id' => [$validacion['error']]
            ]);
        }

        $usuario = $this->repository->findById($id);

        if (!$usuario) {
            throw new NotFoundException('Usuario no encontrado');
        }

        if ($rawData === []) {
            throw new BadRequestException(
                'Debe enviar al menos un campo para actualizar'
            );
        }

        // El contrato no permite modificar estos campos.
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

        $validacion = UsuarioValidator::validarActualizacionParcial($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        if (
            array_key_exists('email', $data)
            && $this->repository->existsByEmail($data['email'], $id)
        ) {
            throw new ValidationException([
                'email' => [
                    'El email ya está registrado'
                ]
            ]);
        }

        if (array_key_exists('contrasena', $data)) {
            $data['contrasena'] = password_hash(
                $data['contrasena'],
                PASSWORD_DEFAULT
            );
        }

        $this->repository->update($usuario, $data);
    }

    public function restaurar($rawId): void
    {
        $id = UsuarioSanitizer::sanitizarIdUsuario($rawId);

        $validacion = UsuarioValidator::validarSoloIdUsuario($id);

        if (!$validacion['success']) {
            throw new ValidationException([
                'id' => [$validacion['error']]
            ]);
        }

        $usuario = $this->repository->findDeletedById($id);

        if (!$usuario) {
            throw new NotFoundException(
                'Usuario eliminado no encontrado'
            );
        }

        $usuario = $this->repository->existsByEmail($usuario->email) ? null : $usuario;

        if (!$usuario) {
            throw new BadRequestException(
                'No se puede restaurar el usuario porque el email ya está registrado'
            );
        }

        $this->repository->restore($usuario);
    }
}