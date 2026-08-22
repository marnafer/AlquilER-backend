<?php

namespace App\Controllers\Api;

use App\Models\LogActividad;
use App\Models\Usuario;
use App\Sanitizers\LogActividadSanitizer;
use App\Validators\LogActividadValidator;
use App\Helpers\Response;
use Exception;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BadRequestException;


class LogActividadController
{
    /**
     * GET /api/logs-actividad
     */
    public function index()
    {
        try {
            $logs = LogActividad::getAll();

            Response::success(
                $logs,
                200,
                'Lista de logs obtenida correctamente'
            );
        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/logs-actividad/{id}
     */
    public function show($id)
    {
        $val = LogActividadValidator::validarSoloId($id);
        if (!$val['success']) {
            throw new ValidationException($val['errors']);
        }

        try {
            $log = LogActividad::getById($id);

            if (!$log) {
                throw new NotFoundException('Log no encontrado');
            }

            Response::success($log);
        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/logs-actividad/usuario/{id}
     */
    public function getByUsuario($usuarioId)
    {
        if (!is_numeric($usuarioId) || $usuarioId <= 0) {
            throw new ValidationException([
                'usuario_id' => 'Debe ser un número positivo'
            ]);
        }   

        try {
            // 1. verificar que el usuario exista
            $usuario = Usuario::find($usuarioId);

            if (!$usuario) {
                throw new NotFoundException('Usuario no encontrado');
            }

            // 2. obtener logs
            $logs = LogActividad::where('usuario_id', $usuarioId)->get();

            Response::success([
                'items' => $logs,
                'total' => $logs->count(),
                'usuario_id' => (int)$usuarioId
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/logs-actividad/fecha?desde=X&hasta=Y
     */
    public function getByFecha()
    {
        $desde = $_GET['desde'] ?? null;
        $hasta = $_GET['hasta'] ?? null;

        if (!$desde || !$hasta) {
            throw new BadRequestException('Las fechas desde y hasta son requeridas');
        }

        if (!strtotime($desde) || !strtotime($hasta)) {
            throw new ValidationException([
                'fecha' => 'Formato de fecha inválido (YYYY-MM-DD)'
            ]);
        }

        try {
            $logs = LogActividad::getByFechaRango($desde, $hasta);

            Response::success([
                'desde' => $desde,
                'hasta' => $hasta,
                'total' => count($logs),
                'items' => $logs
            ]);
        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/logs-actividad/buscar?q=texto
     */
    public function search()
    {
        $q = $_GET['q'] ?? null;

        if (!$q) {
            return throw new BadRequestException('El término de búsqueda es requerido');
        }

        $q = trim($q);
        $q = preg_replace('/\s+/', ' ', $q);

        try {
            $logs = LogActividad::where('accion', 'LIKE', "%$q%")->get();

            return Response::success([
                'items' => $logs,
                'total' => $logs->count(),
                'busqueda' => $q
            ]);

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * GET /api/logs-actividad/estadisticas
     */
    public function getEstadisticas()
    {
        try {
            $data = LogActividad::getEstadisticas();

            Response::success($data);
        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * POST /api/logs-actividad/registrar
     */
    public function registrar()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {
            throw new BadRequestException('JSON inválido');
        }

        $ipOriginal = $data['ip_address'] ?? null;

        $san = LogActividadSanitizer::sanitizar($data);

        $validacion = LogActividadValidator::validarCrear($san);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        if (
            $ipOriginal !== null &&
            $ipOriginal !== '' &&
            !filter_var($ipOriginal, FILTER_VALIDATE_IP)
        ) {
            throw new ValidationException([
                'ip_address' => 'La dirección IP no es válida'
            ]);
        }

        if (!Usuario::find($san['usuario_id'])) {
            throw new ValidationException([
                'usuario_id' => 'El usuario indicado no existe'
            ]);
        }

        if (empty($san['ip_address'])) {
            $san['ip_address'] = LogActividadSanitizer::getClientIp();
        }

        try {

            $san['fecha'] = date('Y-m-d H:i:s');

            $log = LogActividad::create($san);

            Response::created(
                $log,
                'Log registrado correctamente'
            );

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * DELETE /api/logs-actividad/limpiar/antiguos?dias=30
     */
    public function limpiarAntiguos()
    {
        $dias = $_GET['dias'] ?? 30;

        $validacion = LogActividadValidator::validarDias($dias);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $dias = (int)$dias;

        try {
            $fechaLimite = date('Y-m-d H:i:s', strtotime("-{$dias} days"));

            $eliminados = LogActividad::where('fecha', '<', $fechaLimite)->delete();

            Response::success([
                'dias' => $dias,
                'eliminados' => $eliminados
            ], 200, "Se eliminaron {$eliminados} logs");

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * DELETE /api/logs-actividad/usuario/{id}
     */
    public function limpiarPorUsuario($usuarioId)
    {
        $validacion = LogActividadValidator::validarSoloId($usuarioId);

        if (!$validacion['success']) {
            throw new ValidationException($validacion['errors']);
        }

        $usuario = Usuario::find((int)$usuarioId);

        if (!$usuario) {
            throw new NotFoundException('Usuario no encontrado');
        }

        try {
            $eliminados = LogActividad::where('usuario_id', (int)$usuarioId)->delete();

            Response::success([
                'usuario_id' => (int)$usuarioId,
                'eliminados' => $eliminados
            ], 200, 'Logs del usuario eliminados');

        } catch (\Throwable $exception) {
            throw $exception;
        }
    }

    /**
     * DELETE /api/logs-actividad/{id}
     */
    public function delete($id)
    {
        $val = LogActividadValidator::validarSoloId($id);
        if (!$val['success']) {
            throw new ValidationException($val['errors']);
        }

        try {
            $log = LogActividad::find($id);

            if (!$log) {
                throw new NotFoundException('Log no encontrado');
            }

            $log->delete();

            Response::success([], 200, 'Log eliminado correctamente');
        } catch (\Throwable $exception) {
            throw $exception;
        }
    }
}