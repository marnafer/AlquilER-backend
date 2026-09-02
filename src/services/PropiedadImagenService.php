<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BadRequestException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\PropiedadImagen;
use App\Models\Propiedad;
use App\Repositories\PropiedadImagenRepositoryInterface;
use App\Sanitizers\PropiedadImagenSanitizer;
use App\Validators\PropiedadImagenValidator;
use App\Services\PropiedadService;

class PropiedadImagenService
{

    public function __construct(
        private readonly PropiedadImagenRepositoryInterface $repository,
        private readonly PropiedadService $propiedadService,
        private readonly LogActividadService $logActividadService
    ) {
    }

    private function verificarPermiso(Propiedad $propiedad, object $user): void {
        if (
            (int) $user->rol_id !== 2 &&
            (int) $propiedad->usuario_id !== (int) $user->sub
        ) {
            throw new ForbiddenException(
                'No tiene permisos sobre esta propiedad'
            );
        }
    }

    public function listar($rawId = null): array
    {
        if ($rawId === null || $rawId === '') {
            $imagenes = $this->repository->all();

            return [
                'items' => $imagenes,
                'total' => $imagenes->count(),
            ];
        }

        $id = PropiedadImagenSanitizer::sanitizarIdPropiedadImagen($rawId);

        $validacion = PropiedadImagenValidator::validarSoloIdPropiedadImagen($id);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $imagen = $this->repository->findById($id);

        if (!$imagen) {
            throw new NotFoundException('Imagen no encontrada');
        }

        return [
            'items' => [$imagen],
            'total' => 1,
        ];
    }

    public function obtener($rawId): PropiedadImagen
    {
        $id = PropiedadImagenSanitizer::sanitizarIdPropiedadImagen($rawId);

        $validacion = PropiedadImagenValidator::validarSoloIdPropiedadImagen($id);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $imagen = $this->repository->findById($id);

        if (!$imagen) {
            throw new NotFoundException('Imagen no encontrada');
        }

        return $imagen;
    }

    public function crear(array $rawData, $file, object $user): PropiedadImagen
    {
        $data = PropiedadImagenSanitizer::sanitizarPropiedadImagen($rawData);

        $validacion = PropiedadImagenValidator::validarCrearPropiedadImagen($data, $file);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $propiedadId = (int) $data['propiedad_id'];

        $propiedad = $this->propiedadService->obtener($propiedadId);

        $this->verificarPermiso($propiedad, $user);

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/propiedades';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $nombreArchivo = time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $destino = $uploadDir . '/' . $nombreArchivo;

        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            throw new BadRequestException('Error al guardar la imagen');
        }

        $cantidadImagenes = $this->repository->countByPropiedadId($propiedadId);

        $this->logActividadService->registrar(
            (int) $user->sub,
            'Creación de imagen para propiedad ID: ' . $propiedadId
        );

        return $this->repository->create([
            'propiedad_id' => $propiedadId,
            'ruta' => '/uploads/propiedades/' . $nombreArchivo,
            'descripcion' => $data['descripcion'],
            'es_principal' => $cantidadImagenes === 0 ? 1 : 0,
        ]);
    }

    public function establecerPrincipal($rawId, object $user): PropiedadImagen
    {
        $imagen = $this->obtener($rawId);

        $this->verificarPermiso($imagen->propiedad, $user);

        $this->repository->clearPrincipalByPropiedadId((int) $imagen->propiedad_id);
        $this->repository->setPrincipal($imagen);

        return $imagen->refresh();
    }

    public function eliminar($rawId, object $user): void
    {
        $imagen = $this->obtener($rawId);

        $this->verificarPermiso($imagen->propiedad, $user);

        $rutaFisica = dirname(__DIR__, 2) . '/public' . $imagen->ruta;

        if (file_exists($rutaFisica)) {
            unlink($rutaFisica);
        }

        $this->repository->delete($imagen);
        $this->logActividadService->registrar(
            (int) $user->sub,
            'Eliminación de imagen para propiedad ID: ' . $imagen->propiedad_id
        );
    }
}
