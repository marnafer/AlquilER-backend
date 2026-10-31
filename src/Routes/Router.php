<?php

declare(strict_types=1);

namespace App\Routes;

use App\Exceptions\MethodNotAllowedException;
use App\Exceptions\NotFoundException;
use App\Http\Request;
use App\Http\Response;

/**
 * Mapea peticiones a acciones de controlador.
 *
 * La acción se invoca siempre con el Request como primer argumento y se
 * espera que devuelva un Response. Así el despacho es determinista (no hay
 * reflexión ni estado global) y los controladores son testables puros.
 */
class Router
{
    private array $routes = [];
    private ?string $matchedRoute = null;

    public function get(string $uri, callable|array $handler): void
    {
        $this->routes['GET'][$uri] = $handler;
    }

    public function post(string $uri, callable|array $handler): void
    {
        $this->routes['POST'][$uri] = $handler;
    }

    public function put(string $uri, callable|array $handler): void
    {
        $this->routes['PUT'][$uri] = $handler;
    }

    public function patch(string $uri, callable|array $handler): void
    {
        $this->routes['PATCH'][$uri] = $handler;
    }

    public function delete(string $uri, callable|array $handler): void
    {
        $this->routes['DELETE'][$uri] = $handler;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();
        $this->matchedRoute = null;

        // 1. Rutas exactas
        if (isset($this->routes[$method][$path])) {
            $this->matchedRoute = $path;

            return $this->invocarAccion(
                $this->routes[$method][$path],
                $request
            );
        }

        // 2. Rutas dinámicas
        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $pattern = $this->compilarRuta($route);

            if (preg_match($pattern, $path, $matches)) {
                $this->matchedRoute = $route;
                array_shift($matches);

                return $this->invocarAccion(
                    $handler,
                    $request,
                    $matches
                );
            }
        }

        // 3. Método incorrecto
        foreach ($this->routes as $routes) {
            foreach (array_keys($routes) as $route) {
                if (preg_match($this->compilarRuta($route), $path)) {
                    $this->matchedRoute = $route;

                    throw new MethodNotAllowedException();
                }
            }
        }

        throw new NotFoundException();
    }

    /**
     * Convierte una ruta con {parametros} en una expresión regular anclada.
     */
    private function compilarRuta(string $route): string
    {
        $pattern = preg_replace(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            '([^/]+)',
            $route
        );

        return '#^' . $pattern . '$#';
    }

    /**
     * Invoca la acción del controlador con el Request y los parámetros de la
     * ruta, y devuelve su Response.
     */
    private function invocarAccion(
        callable|array $handler,
        Request $request,
        array $params = []
    ): Response {
        [$controller, $action] = $handler;

        $instance = is_object($controller)
            ? $controller
            : new $controller();

        /** @var Response $response */
        $response = $instance->$action($request, ...$params);

        return $response;
    }

    public function matchedRoute(): ?string
    {
        return $this->matchedRoute;
    }
}
