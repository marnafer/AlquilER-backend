<?php

declare(strict_types=1);

namespace App\Performance;

final class PerformanceMetricsContext
{
    private static ?PerformanceMetrics $current = null;

    public static function activate(PerformanceMetrics $metrics): void
    {
        self::$current = $metrics;
    }

    public static function current(): ?PerformanceMetrics
    {
        return self::$current;
    }

    public static function clear(): void
    {
        self::$current = null;
    }
}