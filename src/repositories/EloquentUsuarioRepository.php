<?php

namespace App\Repositories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Collection;

class EloquentUsuarioRepository implements UsuarioRepositoryInterface
{
    public function all(array $filtros = []): Collection
    {
        $query = Usuario::query();

        if (!empty($filtros['solo_eliminados'])) {
            $query->onlyTrashed();
        } elseif (!empty($filtros['incluir_eliminados'])) {
            $query->withTrashed();
        }

        return $query
            ->orderByDesc('id')
            ->get();
    }

    public function findById(int $id): ?Usuario
    {
        return Usuario::query()
            ->whereKey($id)
            ->first();
    }

    public function findDeletedById(int $id): ?Usuario
    {
        return Usuario::onlyTrashed()
            ->whereKey($id)
            ->first();
    }

    public function findByIdWithRole(int $id): ?Usuario
    {
        return Usuario::query()
            ->with('rol')
            ->whereKey($id)
            ->first();
    }

    public function findByEmail(string $email): ?Usuario
    {
        return Usuario::query()
            ->where('email', $email)
            ->first();
    }

    public function existsByEmail(
        string $email,
        ?int $exceptId = null
    ): bool {
        $query = Usuario::query()
            ->where('email', $email);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    public function create(array $data): Usuario
    {
        return Usuario::create($data);
    }

    public function createWithRole(
        array $data,
        int $roleId
    ): Usuario {
        $usuario = new Usuario($data);

        $usuario->rol_id = $roleId;
        $usuario->save();

        return $usuario;
    }

    public function update(
        Usuario $usuario,
        array $data
    ): bool {
        return $usuario->update($data);
    }

    public function delete(Usuario $usuario): bool
    {
        return (bool) $usuario->delete();
    }

    public function restore(Usuario $usuario): bool
    {
        return (bool) $usuario->restore();
    }
}