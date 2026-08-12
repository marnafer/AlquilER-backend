<?php

namespace App\Controllers\Api;

use App\Models\Servicio;
use App\Helpers\Response;
use App\Sanitizers\ServicioSanitizer;
use App\Validators\ServicioValidator;

class ServicioController
{
    public function listar()
    {
        try {

            $servicios = Servicio::all();

            Response::success([
                'items' => $servicios,
                'total' => $servicios->count()
            ]);

        } catch (\Exception $e) {

            Response::serverError($e->getMessage());
        }
    }

    public function obtener($id)
    {
        $idSan = ServicioSanitizer::sanitizarIdServicio($id);

        $val = ServicioValidator::validarIdServicio($idSan);

        if (!$val['success']) {
            Response::validationError([
                'id' => [$val['error']]
            ]);
        }

        try {

            $servicio = Servicio::find($idSan);

            if (!$servicio) {
                Response::notFound('Servicio no encontrado');
            }

            Response::success($servicio);

        } catch (\Exception $e) {

            Response::serverError($e->getMessage());
        }
    }

    public function crear()
    {
        $raw = json_decode(file_get_contents('php://input'), true) ?? [];

        $san = ServicioSanitizer::sanitizarServicio($raw);

        $val = ServicioValidator::validarServicio($san);

        if (!$val['success']) {
            Response::validationError($val['errors']);
        }

        try {

            if (Servicio::where('nombre', $san['nombre'])->exists()) {
                Response::badRequest('Ya existe un servicio con este nombre');
            }

            $servicio = Servicio::create([
                'nombre' => $san['nombre']
            ]);

            Response::created(
                $servicio,
                'Servicio creado exitosamente'
            );

        } catch (\Exception $e) {

            Response::serverError($e->getMessage());
        }
    }

    public function actualizar($id)
    {
        $raw = json_decode(file_get_contents('php://input'), true) ?? [];

        $raw['id'] = $id;

        $san = ServicioSanitizer::sanitizarServicio($raw);

        $val = ServicioValidator::validarServicio($san, true);

        if (!$val['success']) {
            Response::validationError($val['errors']);
        }

        try {

            $servicio = Servicio::find($san['id']);

            if (!$servicio) {
                Response::notFound('Servicio no encontrado');
            }

            if (
                Servicio::where('nombre', $san['nombre'])
                    ->where('id', '!=', $san['id'])
                    ->exists()
            ) {
                Response::badRequest('Ya existe otro servicio con este nombre');
            }

            $servicio->update([
                'nombre' => $san['nombre']
            ]);

            Response::success(
                $servicio,
                200,
                'Servicio actualizado exitosamente'
            );

        } catch (\Exception $e) {

            Response::serverError($e->getMessage());
        }
    }

    public function eliminar($id)
    {
        $idSan = ServicioSanitizer::sanitizarIdServicio($id);

        $val = ServicioValidator::validarIdServicio($idSan);

        if (!$val['success']) {
            Response::validationError([
                'id' => [$val['error']]
            ]);
        }

        try {

            $servicio = Servicio::find($idSan);

            if (!$servicio) {
                Response::notFound('Servicio no encontrado');
            }

            if ($servicio->propiedades()->exists()) {
                Response::badRequest(
                    'No se puede eliminar el servicio porque está asociado a propiedades'
                );
            }

            $servicio->delete();

            Response::success(
                [],
                200,
                'Servicio eliminado exitosamente'
            );

        } catch (\Exception $e) {

            Response::serverError($e->getMessage());
        }
    }
}