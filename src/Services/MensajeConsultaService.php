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
        private readonly LogActividadService $logActividadService,
        private readonly NotificacionService $notificacionService
    ) {
    }

    public function crearMensaje(
        array $rawData,
        int $usuarioLogueadoId,
        ?int $rolId = null
    ): MensajeConsulta {
        $data = MensajeConsultaSanitizer::sanitizarMensajeConsulta($rawData);
        $data['usuario_id'] = $usuarioLogueadoId;

        $validacion = MensajeConsultaValidator::validarCrearMensajeConsulta($data);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        // Delega la búsqueda y validación de seguridad a la Policy mediante ConsultaService
        $consulta = $this->consultaService->obtenerConsultaAutorizada(
            $data['consulta_id'],
            $usuarioLogueadoId,
            $rolId
        );

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

        // Notifica al otro participante de la consulta (interesado o propietario)
        $propietarioId = null;

        if (
            $consulta->relationLoaded('propiedad')
            && $consulta->propiedad
        ) {
            $propietarioId = (int) $consulta->propiedad->usuario_id;
        }

        $destinatarioId = ($usuarioLogueadoId === (int) $consulta->usuario_id)
            ? $propietarioId
            : (int) $consulta->usuario_id;

        if ($destinatarioId && $destinatarioId !== $usuarioLogueadoId) {
            $this->notificacionService->crear(
                $destinatarioId,
                'mensaje_nuevo',
                'Nuevo mensaje en consulta',
                "Recibiste un nuevo mensaje en la consulta #{$consulta->id}.",
                (int) $consulta->id
            );
        }

        return $mensaje;
    }

    public function obtenerHistorial(
        int $consultaId,
        int $usuarioLogueadoId,
        ?int $rolId = null,
        $antesDeId = null,
        $despuesDeId = null,
        $limite = 10
    ): array {

         // Establecer el límite predeterminado.
        if ($limite === null) {
            $limite = 10;
        }
        
        $parametros = [
            'limite' => $limite,
        ];

        if ($antesDeId !== null) {
            $parametros['antes_de_id'] = $antesDeId;
        }

        if ($despuesDeId !== null) {
            $parametros['despues_de_id'] = $despuesDeId;
        }

        $validacion = MensajeConsultaValidator::validarPaginacion(
            $parametros
        );

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        // A partir de aquí, los parámetros ya fueron validados.
        $limite = (int) $limite;
        $antesDeId = $antesDeId !== null
            ? (int) $antesDeId
            : null;
        $despuesDeId = $despuesDeId !== null
            ? (int) $despuesDeId
            : null;

        // Autorizar antes de consultar los mensajes.
        $this->consultaService->obtenerConsultaAutorizada(
            $consultaId,
            $usuarioLogueadoId,
            $rolId
        );

        if ($despuesDeId !== null) {
            // Pedimos un registro adicional para detectar si quedan más.
            $mensajes = $this->mensajeRepository->findNewerByConsultaId(
                $consultaId,
                $despuesDeId,
                $limite + 1
            );

            $hayMasNuevos = $mensajes->count() > $limite;

            $mensajes = $mensajes
                ->take($limite)
                ->values();

            return [
                'items' => $mensajes,
                'total' => $mensajes->count(),
                'hay_anteriores' => false,
                'hay_mas_nuevos' => $hayMasNuevos,
            ];
        }

        if ($antesDeId !== null) {
            $mensajes = $this->mensajeRepository->findOlderByConsultaId(
                $consultaId,
                $antesDeId,
                $limite + 1
            );
        } else {
            $mensajes = $this->mensajeRepository->findLatestByConsultaId(
                $consultaId,
                $limite + 1
            );
        }

        $hayAnteriores = $mensajes->count() > $limite;

        // El repositorio entrega los más recientes primero en estas consultas.
        // Para la interfaz, los mensajes deben quedar en orden ascendente.
        $mensajes = $mensajes
            ->take($limite)
            ->reverse()
            ->values();

        return [
            'items' => $mensajes,
            'total' => $mensajes->count(),
            'hay_anteriores' => $hayAnteriores,
            'hay_mas_nuevos' => false,
        ];
    }
}