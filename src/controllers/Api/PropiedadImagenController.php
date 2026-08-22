<?php

namespace App\Controllers\Api;

use App\Models\PropiedadImagen;
use App\Sanitizers\PropiedadImagenSanitizer;
use App\Validators\PropiedadImagenValidator;
use App\Helpers\Response;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

class PropiedadImagenController
{
    /**
     * GET /api/propiedad-imagenes
     */
    public function indexApi()
    {
        try {

            $propiedadId = $_GET['propiedad_id'] ?? null;

            $query = PropiedadImagen::query();

            if ($propiedadId) {

                $propiedadId = (int) $propiedadId;

                $query->where(
                    'propiedad_id',
                    $propiedadId
                );
            }

            $imagenes = $query
                ->orderBy('id', 'desc')
                ->get();

            Response::success([
                'items' => $imagenes,
                'total' => $imagenes->count()
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/propiedad-imagenes/{id}
     */
    public function mostrarApi($id)
    {
        $idSan = PropiedadImagenSanitizer::sanitizarIdPropiedadImagen($id);

        if ($idSan === null) {
            throw new ValidationException([
                'id' => 'El ID de imagen es requerido, debe ser un entero positivo.'
            ]);
            return;
        }

        try {

            $img = PropiedadImagen::find($idSan);

            if (!$img) {
                throw new NotFoundException('Imagen no encontrada');
                return;
            }

            Response::success([
                'data' => $img
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * POST /api/propiedad-imagenes
     */
    public function crear()
    {
        $raw = $_POST;
        $file = $_FILES['imagen'] ?? null;

        $san = PropiedadImagenSanitizer::sanitizarPropiedadImagen($raw);

        $validacion = PropiedadImagenValidator::validarCrearPropiedadImagen($san, $file);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        try {

            $uploadDir = dirname(SRC_PATH) . '/public/uploads/propiedades';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);

            $nombreArchivo =
                time() . '_' . bin2hex(random_bytes(6)) . '.' . strtolower($ext);

            $destino = $uploadDir . '/' . $nombreArchivo;

            if (!move_uploaded_file($file['tmp_name'], $destino)) {
                throw new \Exception('Error al guardar imagen');
            }

            // Verificar si la propiedad ya tiene imágenes para asignar es_principal
            $cantidadImagenes = PropiedadImagen::where(
                'propiedad_id',
                $san['propiedad_id']
            )->count();

            $rutaRelativa = '/uploads/propiedades/' . $nombreArchivo;
            $registro = PropiedadImagen::create([
                'propiedad_id' => $san['propiedad_id'],
                'ruta' => $rutaRelativa, // <-- Guardamos sin el 'public/'
                'descripcion' => $san['descripcion'],
                'es_principal' => $cantidadImagenes === 0 ? 1 : 0
            ]);

            Response::created($registro->toArray(), 'Imagen creada correctamente');

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }


    /**
    *PUT /api/propiedad-imagenes/{id}/principal
    */
    public function establecerPrincipal($id)
    {
        $imagen = PropiedadImagen::find($id);

        if (!$imagen) {
            throw new NotFoundException('Imagen no encontrada');
            return;
        }

        PropiedadImagen::where(
            'propiedad_id',
            $imagen->propiedad_id
        )->update([
            'es_principal' => 0
        ]);

        $imagen->es_principal = 1;
        $imagen->save();

        Response::success([
            'data' => $imagen,
            'message' => 'Imagen principal actualizada'
        ]);
    }

    /**
     * DELETE /api/propiedad-imagenes/{id}
     */
    public function eliminar($id)
    {
        $idSan = PropiedadImagenSanitizer::sanitizarIdPropiedadImagen($id);

        if ($idSan === null) {
            throw new ValidationException([
                'id' => 'El ID de imagen es requerido'
            ]);
            return;
        }

        try {

            $img = PropiedadImagen::find($idSan);

            if (!$img) {
                throw new NotFoundException('Imagen no encontrada');
                return;
            }

            $rutaFisica = dirname(dirname(__DIR__)) . '/public' . $img->ruta;

            if (file_exists($rutaFisica)) {
                unlink($rutaFisica);
            }

            // NO PASAR STRING EN EL 2DO PARAMETRO
            Response::success([
                'message' => 'Imagen eliminada correctamente'
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }
}