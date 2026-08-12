<?php

namespace App\Controllers\Api;

use App\Models\PropiedadServicio;
use App\Models\Servicio;
use App\Sanitizers\PropiedadServicioSanitizer;
use App\Validators\PropiedadServicioValidator;
use App\Helpers\Response;
use Exception;

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

        } catch (Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/propiedades-servicios/{id}
     */
    public function show($id)
    {
        $validacion = PropiedadServicioValidator::validarSoloId($id);

        if (!$validacion['success']) {
            Response::validationError($validacion['errors']);
        }

        try {
            $relacion = PropiedadServicio::with(['propiedad', 'servicio'])
                ->find($id);

            if (!$relacion) {
                Response::notFound('Relación no encontrada');
            }

            Response::success($relacion);

        } catch (Exception $e) {
            Response::serverError($e->getMessage());
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
            Response::validationError($val['errors']);
        }

        try {
            // evitar duplicados
            if (
                PropiedadServicio::where('propiedad_id', $san['propiedad_id'])
                    ->where('servicio_id', $san['servicio_id'])
                    ->exists()
            ) {
                Response::badRequest('Esta propiedad ya tiene ese servicio');
            }

            $relacion = PropiedadServicio::create($san);

            Response::created(
                $relacion,
                'Servicio asignado a la propiedad correctamente'
            );

        } catch (Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * DELETE /api/propiedades-servicios/{id}
     */
    public function delete($id)
    {
        $validacion = PropiedadServicioValidator::validarSoloId($id);

        if (!$validacion['success']) {
            Response::validationError($validacion['errors']);
        }

        try {
            $relacion = PropiedadServicio::find($id);

            if (!$relacion) {
                Response::notFound('Relación no encontrada');
            }

            $relacion->delete();

            Response::success([], 200, 'Relación eliminada correctamente');

        } catch (Exception $e) {
            Response::serverError($e->getMessage());
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

        } catch (Exception $e) {
            Response::serverError($e->getMessage());
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

        } catch (Exception $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * POST /api/propiedades-servicios/sync/{propiedadId}
     */
    public function sync($propiedadId)
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['servicios_ids']) || !is_array($data['servicios_ids'])) {
            Response::badRequest('Debe enviar un array de servicios_ids');
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
                Response::validationError([
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

        } catch (\Exception $e) {

            Response::serverError($e->getMessage());
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

        } catch (Exception $e) {
            Response::serverError($e->getMessage());
        }
    }
}