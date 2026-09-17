<?php

namespace App\Repositories;

use App\Models\MensajeConsulta;
use Illuminate\Database\Eloquent\Collection;

interface MensajeConsultaRepositoryInterface
{
    /**
     * Crea un nuevo mensaje en la base de datos.
     */
    public function create(array $data): MensajeConsulta;

    /**
     * Obtiene todos los mensajes de una consulta específica, ordenados por fecha.
     */
    public function findByConsultaId(int $consultaId): Collection;

    /**
     * Busca un mensaje específico por su ID.
     */
    public function findById(int $id): ?MensajeConsulta;

    /**
     * Actualiza el contenido de un mensaje existente.
     */
    public function update(int $id, array $data): bool;

    /**
     * Elimina un mensaje (Soft Delete).
     */
    public function delete(int $id): void;
}