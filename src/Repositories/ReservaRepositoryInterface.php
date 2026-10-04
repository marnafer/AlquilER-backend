<?php

namespace App\Repositories;

interface ReservaRepositoryInterface
{
    public function getAll(array $filtros = []): array;

    public function findById(int $id);

    public function findDeletedById(int $id);

    public function create(array $data): int;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function restore(int $id): bool;

    public function getByUsuario(int $usuarioId): array;

    public function getByPropiedad(int $propiedadId): array;

    public function tieneReservaActiva(int $propiedadId): bool;

    /**
     * Indica si la propiedad ya tiene una reserva confirmada que se solapa
     * con el rango [$fechaInicio, $fechaFin].
     *
     * Una reserva con fecha_fin_alquiler nula se trata como abierta: ocupa
     * desde su fecha de inicio en adelante. Un rango con fin nulo se trata
     * igual, como si extendiera hasta el infinito.
     */
    public function hayReservaConfirmadaSolapada(
        int $propiedadId,
        string $fechaInicio,
        ?string $fechaFin = null,
        ?int $excluirId = null
    ): bool;

    public function finalizarVencidas(): int;
}