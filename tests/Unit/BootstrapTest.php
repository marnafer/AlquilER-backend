<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Routes\Router;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class BootstrapTest extends TestCase
{
    public function test_carga_las_rutas_de_api(): void
    {
        $router = new Router();

        require __DIR__ . '/../../src/Routes/api.php';

        $reflection = new ReflectionClass(Router::class);
        $property = $reflection->getProperty('routes');
        $property->setAccessible(true);

        $routes = $property->getValue($router);

        $this->assertIsArray($routes);
        $this->assertNotEmpty($routes);

        $total = 0;
        foreach ($routes as $methodRoutes) {
            $total += count($methodRoutes);
        }

        $this->assertSame(113, $total);
    }
}

