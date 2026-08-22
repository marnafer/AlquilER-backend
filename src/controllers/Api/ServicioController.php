<?php

namespace App\Controllers\Api;

use App\Models\Servicio;
use App\Helpers\Response;
use App\Sanitizers\ServicioSanitizer;
use App\Validators\ServicioValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BadRequestException;

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

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    public function obtener($id)
    {
        $idSan = ServicioSanitizer::sanitizarIdServicio($id);

        $val = ServicioValidator::validarIdServicio($idSan);

        if (!$val['success']) {
            throw new ValidationException([
                'id' => [$val['error']]
            ]);
        }

        try {

            $servicio = Servicio::find($idSan);

            if (!$servicio) {
                throw new NotFoundException('Servicio no encontrado');
            }

            Response::success($servicio);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    public function crear()
    {
        $raw = json_decode(file_get_contents('php://input'), true) ?? [];

        $san = ServicioSanitizer::sanitizarServicio($raw);

        $val = ServicioValidator::validarServicio($san);

        if (!$val['success']) {
            throw new ValidationException($val['errors']);
        }

        try {

            if (Servicio::where('nombre', $san['nombre'])->exists()) {
                throw new BadRequestException('Ya existe un servicio con este nombre');
            }

            $servicio = Servicio::create([
                'nombre' => $san['nombre']
            ]);

            Response::created(
                $servicio,
                'Servicio creado exitosamente'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    public function actualizar($id)
    {
        $raw = json_decode(file_get_contents('php://input'), true) ?? [];

        $raw['id'] = $id;

        $san = ServicioSanitizer::sanitizarServicio($raw);

        $val = ServicioValidator::validarServicio($san, true);

        if (!$val['success']) {
            throw new ValidationException($val['errors']);
        }

        try {

            $servicio = Servicio::find($san['id']);

            if (!$servicio) {
                throw new NotFoundException('Servicio no encontrado');
            }

            if (
                Servicio::where('nombre', $san['nombre'])
                    ->where('id', '!=', $san['id'])
                    ->exists()
            ) {
                throw new BadRequestException('Ya existe otro servicio con este nombre');
            }

            $servicio->update([
                'nombre' => $san['nombre']
            ]);

            Response::success(
                $servicio,
                200,
                'Servicio actualizado exitosamente'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    public function eliminar($id)
    {
        $idSan = ServicioSanitizer::sanitizarIdServicio($id);

        $val = ServicioValidator::validarIdServicio($idSan);

        if (!$val['success']) {
            throw new ValidationException([
                'id' => [$val['error']]
            ]);
        }

        try {

            $servicio = Servicio::find($idSan);

            if (!$servicio) {
                throw new NotFoundException('Servicio no encontrado');
            }

            if ($servicio->propiedades()->exists()) {
                throw new BadRequestException(
                    'No se puede eliminar el servicio porque está asociado a propiedades'
                );
            }

            $servicio->delete();

            Response::success(
                [],
                200,
                'Servicio eliminado exitosamente'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }
}