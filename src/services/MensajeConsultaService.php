<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\MensajeConsulta;
use App\Repositories\MensajeConsultaRepositoryInterface;
use App\Sanitizers\MensajeConsultaSanitizer;
use App\Validators\MensajeConsultaValidator;

class MensajeConsultaService
{
    public function __construct(
        private readonly MensajeConsultaRepositoryInterface $mensajeRepository,
        private readonly ConsultaService $consultaService,
        private readonly LogActividadService $logActividadService
    ) {
    }

    public function crearMensaje(array $rawData, int $usuarioLogueadoId): MensajeConsulta
    {
        $data = MensajeConsultaSanitizer::sanitizarMensajeConsulta($rawData);
        $data['usuario_id'] = $usuarioLogueadoId;

        $validacion = MensajeConsultaValidator::validarCrearMensajeConsulta($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        // Delega la búsqueda y validación de seguridad a la Policy mediante ConsultaService
        $consulta = $this->consultaService->obtenerConsultaAutorizada($data['consulta_id'], $usuarioLogueadoId);

        $mensaje = $this->mensajeRepository->create([
            'consulta_id' => $consulta->id,
            'usuario_id' => $usuarioLogueadoId,
            'mensaje' => $data['mensaje'],
            'fecha_mensaje' => date('Y-m-d H:i:s'),
        ]);

        $this->logActividadService->registrar(
            $usuarioLogueadoId,
            "Envió un mensaje en la consulta #{$consulta->id}"
        );

        return $mensaje;
    }

    public function obtenerHistorial(int $consultaId, int $usuarioLogueadoId)
    {
        $this->consultaService->obtenerConsultaAutorizada($consultaId, $usuarioLogueadoId);

        return $this->mensajeRepository->findByConsultaId($consultaId);
    }
}