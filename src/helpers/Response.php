<?php
declare(strict_types=1);

namespace App\Helpers;

class Response
{
	public static function json($data, int $status = 200): void
	{
		if (headers_sent() === false) {
			http_response_code($status);
			header('Content-Type: application/json; charset=utf-8');
			header('X-Content-Type-Options: nosniff');
		}

		try {
			echo json_encode(
				$data,
				JSON_PRETTY_PRINT
				| JSON_UNESCAPED_SLASHES
				| JSON_UNESCAPED_UNICODE
				| JSON_THROW_ON_ERROR
			);
		} catch (\JsonException $e) {
			http_response_code(500);

			echo json_encode([
				'success' => false,
				'error' => 'Error al generar JSON'
			]);
		}

		exit;
	}

	// ===== FUNCIONES DE EXITO =====

	public static function success($data = [], int $status = 200, ?string $message = null): void
	{
		$response = [
			'success' => true,
			'data' => $data
		];

		if ($message !== null) {
			$response['message'] = $message;
		}

		self::json($response, $status);
	}

	public static function created($data = [], ?string $message = null): void
	{
		$response = [
			'success' => true,
			'data' => $data
		];

		if ($message !== null) {
			$response['message'] = $message;
		}

		self::json($response, 201);
	}

	public static function noContent(): void
	{
		http_response_code(204);
		exit;
	}

	// ===== FUNCIONES DE ERROR =====

	private static function errorResponse(string $message, int $status): void
	{
		self::json([
			'success' => false,
			'error' => $message
		], $status);
	}

	public static function validationError(array $errors): void
	{
		self::json([
			'success' => false,
			'error' => 'Error de validación',
			'validation_errors' => $errors
		], 422);
	}

	public static function notFound(string $message = 'Recurso no encontrado'): void
	{
		self::errorResponse($message, 404);
	}

	public static function methodNotAllowed(): void
	{
		self::errorResponse('Método no permitido', 405);
	}

	public static function unauthorized(string $message = 'No autorizado'): void
	{
		self::errorResponse($message, 401);
	}

	public static function serverError(string $message = 'Error interno del servidor'): void
	{
		self::errorResponse($message, 500);
	}

	public static function forbidden(string $message = 'Acceso denegado'): void
	{
		self::errorResponse($message, 403);
	}

	public static function badRequest(string $message = 'Solicitud inválida'): void
	{
		self::errorResponse($message, 400);
	}
}