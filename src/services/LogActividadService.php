<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\LogActividadRepositoryInterface;
use App\Sanitizers\LogActividadSanitizer;
use App\Validators\LogActividadValidator;

class LogActividadService
{
    public function __construct(
        private readonly LogActividadRepositoryInterface $repository
    ) {
    }

    public function listar()
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

    public function registrar(int $usuarioId, string $accion, ?string $ipAddress = null): void 
    {
        $data = LogActividadSanitizer::sanitizarCrear([
            'usuario_id' => $usuarioId,
            'accion' => $accion,
            'ip_address' => $ipAddress ?? LogActividadSanitizer::getClientIp(),
            'fecha' => date('Y-m-d H:i:s'),
        ]);

        $validacion = LogActividadValidator::validarCrear($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $this->repository->create($data);
    }
}
