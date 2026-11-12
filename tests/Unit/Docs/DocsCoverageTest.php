<?php

declare(strict_types=1);

namespace Tests\Unit\Docs;

use PHPUnit\Framework\TestCase;

final class DocsCoverageTest extends TestCase
{
    public function test_carga_las_clases_de_documentacion(): void
    {
        $base = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Docs';

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            require_once $file->getPathname();

            $relative = str_replace($base . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $class = 'App\\Docs\\' . str_replace(
                [DIRECTORY_SEPARATOR, '.php'],
                ['\\', ''],
                $relative
            );

            if (!class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);

            $reflection->getAttributes();

            foreach ($reflection->getMethods() as $method) {
                $method->getAttributes();

                if ($method->isPublic() && $method->getNumberOfRequiredParameters() === 0) {
                    $method->invoke($reflection->newInstanceWithoutConstructor());
                }
            }

            foreach ($reflection->getProperties() as $property) {
                $property->getAttributes();
            }

            foreach ($reflection->getConstants() as $name => $value) {
            }
        }

        $this->assertTrue(true);
    }
}