<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Helpers\IpHelper;
use App\Repositories\LogActividadRepositoryInterface;
use App\Sanitizers\LogActividadSanitizer;
use App\Validators\LogActividadValidator;
use Illuminate\Support\Collection;

class LogActividadService
{
    public function __construct(
        private readonly LogActividadRepositoryInterface $repository
    ) {
    }

    public function listar(): Collection
    {
        return $this->repository->all();
    }

    public function obtener($rawId): array
    {
        $id = LogActividadSanitizer::sanitizarId($rawId);

        $validacion = LogActividadValidator::validarSoloId($id);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $log = $this->repository->findById($id);

        if (!$log) {
            throw new NotFoundException('Log no encontrado');
        }

        return $log;
    }

    public function registrar(int $usuarioId, string $accion): void
    {
        $data = LogActividadSanitizer::sanitizarCrear([
            'usuario_id' => $usuarioId,
            'accion' => $accion,
            'ip_address' => IpHelper::obtener(),
        ]);

        $validacion = LogActividadValidator::validar($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $this->repository->create($data);
    }
}