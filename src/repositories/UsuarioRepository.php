<?php
namespace App\Repositories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Collection;

class UsuarioRepository
{
    public function all(): Collection
    {
        return Usuario::query()
            ->orderByDesc('id')
            ->get();
    }

    public function findById(int $id): ?Usuario
    {
        return Usuario::query()
        ->whereKey($id)
        ->first();
    }

    public function create(array $data): Usuario
    {
        return Usuario::create($data);
    }

    public function update(Usuario $usuario, array $data): bool
    {
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

    public function findDeletedById(int $id): ?Usuario
    {
        return Usuario::onlyTrashed()->find($id);
    }

    public function existsByEmail(string $email, ?int $exceptId = null): bool
    {
        $query = Usuario::query()
            ->where('email', $email);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    public function findByEmail(string $email): ?Usuario
    {
        return Usuario::query()
            ->where('email', $email)
            ->first();
    }

    public function createWithRole(array $data, int $rolId): Usuario
    {
        $usuario = new Usuario($data);
        $usuario->rol_id = $rolId;
        $usuario->save();

        return $usuario;
    }

    
}