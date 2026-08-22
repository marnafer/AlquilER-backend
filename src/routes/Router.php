<?php

declare(strict_types=1);

namespace App\Routes;

use App\Exceptions\MethodNotAllowedException;
use App\Exceptions\NotFoundException;

class Router
{
    private array $routes = [];

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

    public function dispatch(string $method, string $path): void
    {
        // 1. Rutas exactas
        if (isset($this->routes[$method][$path])) {

            $handler = $this->routes[$method][$path];

            [$controller, $action] = $handler;

            $instance = new $controller();
            $instance->$action();

            return;
        }

        // 2. Rutas dinámicas
        foreach ($this->routes[$method] ?? [] as $route => $handler) {

            $pattern = preg_replace(
                '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
                '([^/]+)',
                $route
            );

            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $path, $matches)) {

                array_shift($matches); // quita match completo

                [$controller, $action] = $handler;

                $instance = new $controller();

                // pasar parámetros al controller
                $instance->$action(...$matches);

                return;
            }
        }

        // 3. Método incorrecto
        foreach ($this->routes as $routes) {
            foreach (array_keys($routes) as $route) {
                $pattern = preg_replace(
                    '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
                    '([^/]+)',
                    $route
                );

                $pattern = '#^' . $pattern . '$#';

                if (preg_match($pattern, $path)) {
                    throw new MethodNotAllowedException();
                }
            }
        }

        throw new NotFoundException();
    }
}