<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Repositories\NotificacionRepositoryInterface;
use App\Sanitizers\ConsultaSanitizer;

class NotificacionService
{
    public function __construct(
        private readonly NotificacionRepositoryInterface $notificacionRepository
    ) {
    }

    public function listarPorUsuario(int $usuarioId): array
    {
        return $this->notificacionRepository->getByUsuario($usuarioId);
    }

    public function listarNoLeidas(int $usuarioId): array
    {
        $items = $this->notificacionRepository->getByUsuario($usuarioId);

        return array_filter(
            $items,
            fn(array $notificacion): bool =>
                (int) $notificacion['leida'] === 0
        );
    }

    public function contarNoLeidas(int $usuarioId): int
    {
        return $this->notificacionRepository->contarNoLeidas($usuarioId);
    }

    public function crear(
        int $usuarioId,
        string $tipo,
        string $titulo,
        string $mensaje,
        ?int $referenciaId = null
    ): void {
        $this->notificacionRepository->create([
            'usuario_id' => $usuarioId,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'referencia_id' => $referenciaId,
            'leida' => 0,
            'fecha_notificacion' => date('Y-m-d H:i:s')
        ]);
    }

    public function marcarLeida(
        $rawId,
        int $usuarioId
    ): bool {
        $id = ConsultaSanitizer::sanitizarId($rawId);

        if ($id === null) {
            throw new NotFoundException(
                'Notificación no encontrada'
            );
        }

        $notificacion = $this->notificacionRepository
            ->findById($id);

        if (!$notificacion) {
            throw new NotFoundException(
                'Notificación no encontrada'
            );
        }

        if ((int) $notificacion->usuario_id !== $usuarioId) {
            throw new ForbiddenException(
                'No tienes permiso para esta notificación'
            );
        }

        return $this->notificacionRepository->update(
            $id,
            ['leida' => 1]
        );
    }

    public function marcarTodasLeidas(int $usuarioId): bool
    {
        return $this->notificacionRepository
            ->marcarTodasLeidas($usuarioId);
    }
}