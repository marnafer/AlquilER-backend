<?php

namespace App\Controllers\Api;

use App\Models\PropiedadServicio;
use App\Models\Servicio;
use App\Sanitizers\PropiedadServicioSanitizer;
use App\Validators\PropiedadServicioValidator;
use App\Helpers\Response;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BadRequestException;

class PropiedadServicioController
{
    /**
     * GET /api/propiedades-servicios
     */
    public function index()
    {
        try {
            $relaciones = PropiedadServicio::with(['propiedad', 'servicio'])->get();

            Response::success([
                'items' => $relaciones,
                'total' => $relaciones->count()
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/propiedades-servicios/{id}
     */
    public function show($id)
    {
        $validacion = PropiedadServicioValidator::validarSoloId($id);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        try {
            $relacion = PropiedadServicio::with(['propiedad', 'servicio'])
                ->find($id);

            if (!$relacion) {
                throw new NotFoundException('Relación no encontrada');
            }

            Response::success($relacion);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * POST /api/propiedades-servicios
     */
    public function store()
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $san = PropiedadServicioSanitizer::sanitizar($data);

        $val = PropiedadServicioValidator::validarCrear($san);

        if (!$val['success']) {
            throw new ValidationException($val['errors']);
        }

        try {
            // evitar duplicados
            if (
                PropiedadServicio::where('propiedad_id', $san['propiedad_id'])
                    ->where('servicio_id', $san['servicio_id'])
                    ->exists()
            ) {
                throw new BadRequestException('Esta propiedad ya tiene ese servicio');
            }

            $relacion = PropiedadServicio::create($san);

            Response::created(
                $relacion,
                'Servicio asignado a la propiedad correctamente'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * DELETE /api/propiedades-servicios/{id}
     */
    public function delete($id)
    {
        $validacion = PropiedadServicioValidator::validarSoloId($id);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        try {
            $relacion = PropiedadServicio::find($id);

            if (!$relacion) {
                throw new NotFoundException('Relación no encontrada');
            }

            $relacion->delete();

            Response::success([], 200, 'Relación eliminada correctamente');

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/propiedades-servicios/propiedad/{id}
     */
    public function getByPropiedad($propiedadId)
    {
        try {
            $relaciones = PropiedadServicio::with('servicio')
                ->where('propiedad_id', $propiedadId)
                ->get();

            Response::success([
                'propiedad_id' => (int)$propiedadId,
                'servicios' => $relaciones,
                'total' => $relaciones->count()
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/propiedades-servicios/servicio/{id}
     */
    public function getByServicio($servicioId)
    {
        try {
            $relaciones = PropiedadServicio::with('propiedad')
                ->where('servicio_id', $servicioId)
                ->get();

            Response::success([
                'servicio_id' => (int)$servicioId,
                'propiedades' => $relaciones,
                'total' => $relaciones->count()
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * POST /api/propiedades-servicios/sync/{propiedadId}
     */
    public function sync($propiedadId)
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['servicios_ids']) || !is_array($data['servicios_ids'])) {
            throw new BadRequestException('Debe enviar un array de servicios_ids');
        }

        try {

            $serviciosIds = array_values(array_unique($data['servicios_ids']));

            // 1. Obtener servicios existentes en una sola consulta
            $serviciosExistentes = Servicio::whereIn('id', $serviciosIds)
                ->pluck('id')
                ->toArray();

            // 2. Detectar servicios inválidos
            $faltantes = array_diff($serviciosIds, $serviciosExistentes);

            if (!empty($faltantes)) {
                throw new ValidationException([
                    'servicios' => 'Existen servicios inválidos: ' . implode(',', $faltantes)
                ]);
            }

            // 3. Eliminar relaciones actuales
            PropiedadServicio::where('propiedad_id', $propiedadId)->delete();

            // 4. Insertar nuevas relaciones
            foreach ($serviciosIds as $servicioId) {
                PropiedadServicio::create([
                    'propiedad_id' => $propiedadId,
                    'servicio_id' => $servicioId
                ]);
            }

            Response::success([
                'propiedad_id' => (int)$propiedadId,
                'total' => count($serviciosIds)
            ], 200, 'Servicios sincronizados correctamente');

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/propiedades-servicios/estadisticas
     */
    public function getEstadisticas()
    {
        try {
            $total = PropiedadServicio::count();

            $porPropiedad = PropiedadServicio::selectRaw('propiedad_id, COUNT(*) as total')
                ->groupBy('propiedad_id')
                ->get();

            $porServicio = PropiedadServicio::selectRaw('servicio_id, COUNT(*) as total')
                ->groupBy('servicio_id')
                ->get();

            Response::success([
                'total_relaciones' => $total,
                'por_propiedad' => $porPropiedad,
                'por_servicio' => $porServicio
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }
}