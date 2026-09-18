<?php

namespace App\Repositories;

interface PropiedadServicioRepositoryInterface
{
    public function getByPropiedad(int $propiedadId): array;

    public function getByServicio(int $servicioId): array;

    public function exists(
        int $propiedadId,
        int $servicioId
    ): bool;

    public function attach(
        int $propiedadId,
        int $servicioId
    ): bool;

    public function detach(
        int $propiedadId,
        int $servicioId
    ): bool;

    public function attachMultiple(
        int $propiedadId,
        array $servicioIds
    ): array;

    public function sync(
        int $propiedadId,
        array $servicioIds
    ): array;

    public function getServicioIdsByPropiedad(
        int $propiedadId
    ): array;
}