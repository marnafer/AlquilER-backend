<?php

declare(strict_types=1);

namespace App\Performance;

use RuntimeException;

final class PerformanceMetricsSession
{
    private readonly string $statePath;

    public function __construct(?string $directory = null)
    {
        $directory ??= dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR
            . 'storage'
            . DIRECTORY_SEPARATOR
            . 'performance-dashboard';

        $this->statePath = $directory
            . DIRECTORY_SEPARATOR
            . 'sessions.json';
    }

    public function activeSessionId(): ?string
    {
        if (!is_file($this->statePath)) {
            return null;
        }

        return $this->withState(
            static fn (array $state): ?string =>
                $state['active_session_id'],
            false
        );
    }

    /**
     * @return array{
     *     active_session_id: ?string,
     *     last_session_id: ?string,
     *     sessions: list<array{id: string, started_at: string, stopped_at: ?string}>,
     *     csrf_token: string
     * }
     */
    public function status(): array
    {
        return $this->withState(
            static fn (array $state): array => $state,
            false
        );
    }

    public function start(): string
    {
        return $this->withState(
            static function (array &$state): string {
                if ($state['active_session_id'] !== null) {
                    throw new RuntimeException(
                        'Ya hay una captura activa.'
                    );
                }

                $sessionId = bin2hex(random_bytes(16));
                $state['active_session_id'] = $sessionId;
                $state['last_session_id'] = $sessionId;
                $state['sessions'][] = [
                    'id' => $sessionId,
                    'started_at' => gmdate('Y-m-d\TH:i:s\Z'),
                    'stopped_at' => null,
                ];

                return $sessionId;
            },
            true
        );
    }

    public function stop(): ?string
    {
        return $this->withState(
            static function (array &$state): ?string {
                $sessionId = $state['active_session_id'];

                if ($sessionId === null) {
                    return null;
                }

                $state['active_session_id'] = null;

                foreach ($state['sessions'] as &$session) {
                    if ($session['id'] === $sessionId) {
                        $session['stopped_at'] = gmdate('Y-m-d\TH:i:s\Z');
                        break;
                    }
                }

                return $sessionId;
            },
            true
        );
    }

    /**
     * @return list<array{id: string, started_at: string, stopped_at: ?string}>
     */
    public function sessions(): array
    {
        return $this->status()['sessions'];
    }

    /**
     * @template T
     * @param callable(array): T $reader
     * @return T
     */
    private function withState(callable $reader, bool $write): mixed
    {
        $directory = dirname($this->statePath);

        if (
            !is_dir($directory)
            && !@mkdir($directory, 0700, true)
            && !is_dir($directory)
        ) {
            throw new RuntimeException(
                "No se pudo crear el directorio de sesiones: {$directory}"
            );
        }

        $lockPath = $this->statePath . '.lock';
        $lock = @fopen($lockPath, 'c');

        if ($lock === false) {
            throw new RuntimeException(
                "No se pudo abrir el bloqueo de sesiones: {$lockPath}"
            );
        }

        try {
            if (!@flock($lock, LOCK_EX)) {
                throw new RuntimeException(
                    "No se pudo bloquear el estado de sesiones: {$lockPath}"
                );
            }

            $stateExisted = is_file($this->statePath);
            $state = $this->readState();
            $result = $reader($state);

            if (!$stateExisted || $write) {
                $this->writeState($state);
            }

            return $result;
        } finally {
            @flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * @return array{
     *     active_session_id: ?string,
     *     last_session_id: ?string,
     *     sessions: list<array{id: string, started_at: string, stopped_at: ?string}>,
     *     csrf_token: string
     * }
     */
    private function readState(): array
    {
        if (!is_file($this->statePath)) {
            return [
                'active_session_id' => null,
                'last_session_id' => null,
                'sessions' => [],
                'csrf_token' => bin2hex(random_bytes(32)),
            ];
        }

        $contents = file_get_contents($this->statePath);

        if ($contents === false) {
            throw new RuntimeException(
                "No se pudo leer el estado de sesiones: {$this->statePath}"
            );
        }

        $state = json_decode($contents, true);

        if (
            !is_array($state)
            || !array_key_exists('active_session_id', $state)
            || !array_key_exists('last_session_id', $state)
            || !isset($state['sessions'], $state['csrf_token'])
            || !is_array($state['sessions'])
            || !is_string($state['csrf_token'])
        ) {
            throw new RuntimeException(
                "El estado de sesiones es inválido: {$this->statePath}"
            );
        }

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     */
    private function writeState(array $state): void
    {
        $json = json_encode(
            $state,
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        if (file_put_contents($this->statePath, $json, LOCK_EX) === false) {
            throw new RuntimeException(
                "No se pudo guardar el estado de sesiones: {$this->statePath}"
            );
        }

        @chmod($this->statePath, 0600);
    }
}
