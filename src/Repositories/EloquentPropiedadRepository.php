<?php

namespace App\Repositories;

use App\Models\Propiedad;
use App\Models\PropiedadImagen;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

class EloquentPropiedadRepository implements PropiedadRepositoryInterface
{


    private function construirConsulta(array $filtros): Builder
    {
        $query = $this->crearQueryBase();

        $this->aplicarFiltroUbicacion($query, $filtros);
        $this->aplicarFiltrosDeServicios($query, $filtros);
        $this->aplicarFiltroPrecio($query, $filtros);
        $this->aplicarFiltrosDeCaracteristicas($query, $filtros);

        return $query;
    }

    private function crearQueryBase(): Builder
    {
        return Propiedad::query()
            ->addSelect([
                'imagen_url' => PropiedadImagen::query()
                    ->select('ruta')
                    ->whereColumn(
                        'propiedad_imagenes.propiedad_id',
                        'propiedades.id'
                    )
                    ->orderByDesc('es_principal')
                    ->orderBy('id')
                    ->limit(1),
            ]);
    }

    private function aplicarFiltroUbicacion(
        Builder $query,
        array $filtros
    ): void {
        if (isset($filtros['categoria_id'])) {
            $query->whereIn(
                'categoria_id',
                $filtros['categoria_id']
            );
        }

        if (isset($filtros['localidad_id'])) {
            $query->whereIn(
                'localidad_id',
                $filtros['localidad_id']
            );
        }
    }

    private function aplicarFiltrosDeServicios(
        Builder $query,
        array $filtros
    ): void {
        if (empty($filtros['servicio_id'])) {
            return;
        }

        foreach ($filtros['servicio_id'] as $servicioId) {
            $query->whereExists(
                function ($subquery) use ($servicioId) {
                    $subquery
                        ->selectRaw('1')
                        ->from('propiedad_servicio')
                        ->whereColumn(
                            'propiedad_servicio.propiedad_id',
                            'propiedades.id'
                        )
                        ->where(
                            'propiedad_servicio.servicio_id',
                            $servicioId
                        );
                }
            );
        }
    }

    private function aplicarFiltroPrecio(
        Builder $query,
        array $filtros
    ): void {
        if (isset($filtros['precio_min'])) {
            $query->where(
                'precio',
                '>=',
                $filtros['precio_min']
            );
        }

        if (isset($filtros['precio_max'])) {
            $query->where(
                'precio',
                '<=',
                $filtros['precio_max']
            );
        }
    }

    private function aplicarFiltrosDeCaracteristicas(
        Builder $query,
        array $filtros
    ): void {
        $this->aplicarFiltrosDeCantidades($query, $filtros);
        $this->aplicarFiltrosDeBanderas($query, $filtros);
    }

    private function aplicarFiltrosDeCantidades(
        Builder $query,
        array $filtros
    ): void {
        $cantidades = [
            'cantidad_ambientes',
            'cantidad_dormitorios',
            'cantidad_banos',
            'capacidad',
        ];

        foreach ($cantidades as $campo) {
            if (isset($filtros[$campo])) {
                $query->where($campo, '>=', $filtros[$campo]);
            }
        }
    }

    private function aplicarFiltrosDeBanderas(
        Builder $query,
        array $filtros
    ): void {
        $banderas = [
            'destacada',
            'acepta_mascotas',
            'acepta_hijos',
            'disponible',
        ];

        foreach ($banderas as $campo) {
            if (isset($filtros[$campo])) {
                $query->where($campo, (int) $filtros[$campo]);
            }
        }
    }

    
    public function all(array $filtros = []): Collection
    {
        $query = $this->construirConsulta($filtros);

        return $query->orderBy('id', 'asc')->get();
    }
   
    public function paginate(
        array $filtros,
        int $pagina,
        int $porPagina
    ): array {
        $query = $this->construirConsulta($filtros);

        $total = (clone $query)->count();

        $ordenamientos = [
            'antiguedad_desc' => ['fecha_publicacion', 'desc', 'desc'],
            'antiguedad_asc'  => ['fecha_publicacion', 'asc', 'asc'],
            'precio_asc'      => ['precio', 'asc', 'asc'],
            'precio_desc'     => ['precio', 'desc', 'desc'],
        ];

        $orden = $filtros['orden'] ?? 'antiguedad_desc';

        [$columna, $direccion, $direccionId] =
            $ordenamientos[$orden]
            ?? $ordenamientos['antiguedad_desc'];

        $items = $query
            ->orderBy($columna, $direccion)
            ->orderBy('id', $direccionId)
            ->forPage($pagina, $porPagina)
            ->get();

        return [
            'items' => $items,
            'total' => $total,
            'pagination' => [
                'current_page' => $pagina,
                'per_page' => $porPagina,
                'last_page' => max(
                    1,
                    (int) ceil($total / $porPagina)
                ),
            ],
        ];
    }

    public function allParaAdmin(array $filtros = []): Collection
    {
        $query = Propiedad::query()
            ->with([
                'usuario',
                'categoria',
                'localidad',
                'imagenes',
                'imagenPrincipal'
            ]);

        if (!empty($filtros['solo_eliminados'])) {
            $query->onlyTrashed();
        } elseif (!empty($filtros['incluir_eliminados'])) {
            $query->withTrashed();
        }

        return $query
            ->orderByDesc('id')
            ->get();
    }

    public function porUsuario(int $usuarioId): Collection
    {
        // servicios viene eager para que el panel del propietario no tenga que
        // pedir /propiedades/{id}/servicios una vez por cada propiedad (N+1).
        return Propiedad::query()
            ->with(['imagenes', 'imagenPrincipal', 'servicios'])
            ->where('usuario_id', $usuarioId)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function existePorUsuario(int $usuarioId): bool
    {
        return Propiedad::query()
            ->where('usuario_id', $usuarioId)
            ->exists();
    }

    public function findById(int $id): ?Propiedad
    {
        return Propiedad::query()
            ->with([
                'categoria',
                'localidad',
                'servicios',
                'imagenes',
                'imagenPrincipal',
            ])
            ->whereKey($id)
            ->first();
    }

    public function findDeletedById(int $id): ?Propiedad
    {
        return Propiedad::onlyTrashed()->find($id);
    }

    public function create(array $data): Propiedad
    {
        return Propiedad::create($data);
    }

    public function update(
        Propiedad $propiedad,
        array $data
    ): bool {
        return $propiedad->update($data);
    }

    public function delete(Propiedad $propiedad): bool
    {
        return (bool) $propiedad->delete();
    }

    public function restore(Propiedad $propiedad): bool
    {
        return (bool) $propiedad->restore();
    }

    public function findByIdForUpdate(int $id): ?Propiedad
    {
        return Propiedad::query()
            ->whereKey($id)
            ->lockForUpdate()
            ->first();
    }
}