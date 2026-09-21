<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\PropiedadImagen;
use App\Policies\PropiedadImagenPolicy;
use App\Repositories\PropiedadImagenRepositoryInterface;
use App\Sanitizers\PropiedadImagenSanitizer;
use App\Validators\CargaImagenValidatorInterface;
use App\Validators\PropiedadImagenValidator;
use Illuminate\Database\Capsule\Manager as DB;

class PropiedadImagenService
{
    public function __construct(
        private readonly PropiedadImagenRepositoryInterface $repository,
        private readonly PropiedadService $propiedadService,
        private readonly LogActividadService $logService,
        private readonly GestorArchivosInterface $gestorArchivos,
        private readonly CargaImagenValidatorInterface $cargaImagenValidator,
        private readonly PropiedadImagenPolicy $policy
    ) {
    }

    public function listar(): array
    {
        $imagenes = $this->repository->all();

        return [
            'items' => $imagenes,
            'total' => $imagenes->count(),
        ];
    }

    public function obtener($rawId): PropiedadImagen
    {
        $id = PropiedadImagenSanitizer::sanitizarIdPropiedadImagen(
            $rawId
        );

        $validacion = PropiedadImagenValidator::validarSoloIdPropiedadImagen(
            $id
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $imagen = $this->repository->findById($id);

        if (!$imagen) {
            throw new NotFoundException(
                'Imagen no encontrada'
            );
        }

        return $imagen;
    }

    public function crear(
        array $rawData,
        $file,
        int $usuarioId,
        int $rolId
    ): PropiedadImagen {
        $data = PropiedadImagenSanitizer::sanitizarPropiedadImagen(
            $rawData
        );

        $validacion = PropiedadImagenValidator::validarCrearPropiedadImagen(
            $data
        );

        if (!$validacion['success']) {
            throw new ValidationException(
                $validacion['errors']
            );
        }

        $errorImagen = $this->cargaImagenValidator->validate($file);

        if ($errorImagen !== null) {
            throw new ValidationException([
                'imagen' => $errorImagen
            ]);
        }

        $propiedadId = (int) $data['propiedad_id'];

        // Se obtiene la propiedad para validar permisos.
        $propiedad = $this->propiedadService->obtener(
            $propiedadId
        );

        $this->policy->gestionarPropiedad(
            $usuarioId,
            $rolId,
            $propiedad
        );

        $uploadDir = dirname(__DIR__, 2)
            . '/public/uploads/propiedades';

        $nombreArchivo = $this->gestorArchivos->upload(
            $file,
            $uploadDir
        );

        $ruta = '/uploads/propiedades/' . $nombreArchivo;

        $imagen = DB::transaction(
            function () use (
                $propiedadId,
                $data,
                $ruta
            ): PropiedadImagen {
                // Bloqueamos la propiedad durante toda la operación.
                $this->propiedadService
                    ->obtenerParaActualizar($propiedadId);

                $cantidadImagenes = $this->repository
                    ->countByPropiedadId($propiedadId);

                return $this->repository->create([
                    'propiedad_id' => $propiedadId,
                    'ruta' => $ruta,
                    'descripcion' => $data['descripcion'],
                    'es_principal' => $cantidadImagenes === 0 ? 1 : 0,
                ]);
            }
        );

        $this->logService->registrar(
            $usuarioId,
            'Creación de imagen para propiedad ID: ' . $propiedadId
        );

        return $imagen;
    }

    public function establecerPrincipal(
        $rawId,
        int $usuarioId,
        int $rolId
    ): PropiedadImagen {
        $imagen = $this->obtener($rawId);

        $this->policy->gestionar(
            $usuarioId,
            $rolId,
            $imagen
        );

        DB::transaction(
            function () use ($imagen): void {
                $this->repository->clearPrincipalByPropiedadId(
                    (int) $imagen->propiedad_id
                );

                $this->repository->setPrincipal($imagen);
            }
        );

        $this->logService->registrar(
            $usuarioId,
            'Imagen principal actualizada para propiedad ID: '
            . $imagen->propiedad_id
        );

        return $imagen->refresh();
    }

    public function eliminar(
        $rawId,
        int $usuarioId,
        int $rolId
    ): void {
        $imagen = $this->obtener($rawId);

        $this->policy->gestionar(
            $usuarioId,
            $rolId,
            $imagen
        );

        $propiedadId = (int) $imagen->propiedad_id;
        $eraPrincipal = (int) $imagen->es_principal === 1;
        $ruta = $imagen->ruta;

        DB::transaction(
            function () use (
                $imagen,
                $propiedadId,
                $eraPrincipal
            ): void {
                $this->repository->delete($imagen);

                if (!$eraPrincipal) {
                    return;
                }

                $imagenesRestantes = $this->repository
                    ->findByPropiedadId($propiedadId);

                if ($imagenesRestantes->isEmpty()) {
                    return;
                }

                $nuevaPrincipal = $imagenesRestantes->first();

                $this->repository->setPrincipal(
                    $nuevaPrincipal
                );
            }
        );

        $this->gestorArchivos->delete($ruta);

        $this->logService->registrar(
            $usuarioId,
            'Eliminación de imagen para propiedad ID: '
            . $propiedadId
        );
    }
}