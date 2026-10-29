<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require_once dirname(__DIR__) . '/vendor/autoload.php';

const STRESS_DATABASE = 'sistema_alquiler_db_dev';
const STRESS_USER_PREFIX = 'stress-';
const STRESS_EMAIL_DOMAIN = 'synthetic.invalid';
const STRESS_PROPERTY_PREFIX = 'STRESS-DATA-';
const DEFAULT_USER_COUNT = 10000;
const DEFAULT_PROPERTY_COUNT = 10000;
const DEFAULT_CONSULTATION_COUNT = 5000;
const DEFAULT_FAVORITE_COUNT = 15000;
const DEFAULT_RESERVATION_COUNT = 2000;
const MAX_ROWS_PER_TABLE = 100000;
const MAX_ESTIMATED_ROWS = 500000;
const BATCH_SIZE = 500;

function usage(): void
{
    echo <<<'HELP'
Genera datos sintéticos relacionados para una prueba de carga local.

Uso:
  php scripts/generar_datos_stress.php [opciones]

Opciones:
  --users=N          Usuarios sintéticos (default: 10000)
  --properties=N     Propiedades sintéticas (default: 10000)
  --consultations=N  Consultas con mensajes (default: 5000)
  --favorites=N      Relaciones de favoritos (default: 15000)
  --reservations=N   Reservas sintéticas (default: 2000)
  --dry-run          Valida la base y muestra el plan (default)
  --execute          Inserta los datos; nunca borra ni trunca registros
  --help             Muestra esta ayuda

Destino permitido: sistema_alquiler_db_dev, en un servidor local y con
APP_ENV=development. No se crean archivos de imágenes. Los emails usan
el dominio reservado .invalid y las contraseñas son aleatorias/desconocidas.
Los registros se identifican con prefijos STRESS y se omiten al repetir.

Ejemplo:
  php scripts/generar_datos_stress.php --dry-run
  php scripts/generar_datos_stress.php --execute
HELP;
    echo PHP_EOL;
}

/**
 * @return array<string, int|string|bool>
 */
function parseArguments(array $arguments): array
{
    $options = [
        'users' => DEFAULT_USER_COUNT,
        'properties' => DEFAULT_PROPERTY_COUNT,
        'consultations' => DEFAULT_CONSULTATION_COUNT,
        'favorites' => DEFAULT_FAVORITE_COUNT,
        'reservations' => DEFAULT_RESERVATION_COUNT,
        'execute' => false,
    ];

    foreach ($arguments as $argument) {
        if ($argument === '--help') {
            usage();
            exit(0);
        }

        if ($argument === '--execute') {
            $options['execute'] = true;
            continue;
        }

        if ($argument === '--dry-run') {
            $options['execute'] = false;
            continue;
        }

        if (!preg_match('/^--([a-z-]+)=(\d+)$/', $argument, $matches)) {
            throw new InvalidArgumentException(
                "Opción inválida: {$argument}. Use --help para ver el uso."
            );
        }

        $name = $matches[1];

        if (!array_key_exists($name, $options) || $name === 'execute') {
            throw new InvalidArgumentException(
                "Opción desconocida: --{$name}."
            );
        }

        $options[$name] = (int) $matches[2];
    }

    foreach (
        ['users', 'properties', 'consultations', 'favorites', 'reservations']
        as $name
    ) {
        if ($options[$name] < 0 || $options[$name] > MAX_ROWS_PER_TABLE) {
            throw new InvalidArgumentException(
                "--{$name} debe estar entre 0 y " . MAX_ROWS_PER_TABLE . '.'
            );
        }
    }

    if ($options['users'] < 2 || $options['properties'] < 1) {
        throw new InvalidArgumentException(
            'Se requieren al menos 2 usuarios y 1 propiedad.'
        );
    }

    $ownerCount = max(1, (int) ceil($options['users'] * 0.2));

    if ($options['properties'] > 0 && $options['users'] - $ownerCount < 1) {
        throw new InvalidArgumentException(
            'La cantidad de usuarios debe dejar al menos un inquilino y un propietario.'
        );
    }

    $estimatedRows = $options['users']
        + $options['properties']
        + ($options['properties'] * 5)
        + ($options['consultations'] * 3)
        + $options['favorites']
        + $options['reservations'];

    if ($estimatedRows > MAX_ESTIMATED_ROWS) {
        throw new InvalidArgumentException(
            'La carga estimada supera el límite de seguridad de '
            . MAX_ESTIMATED_ROWS
            . ' filas por ejecución. Reduzca los parámetros.'
        );
    }

    return $options;
}

function connectToTestDatabase(string $projectRoot): PDO
{
    Dotenv::createImmutable($projectRoot)->safeLoad();

    $environment = trim((string) ($_ENV['APP_ENV'] ?? ''));
    $host = trim((string) ($_ENV['DB_HOST'] ?? ''));
    $database = trim((string) ($_ENV['DB_DATABASE'] ?? ''));

    if ($environment !== 'development') {
        throw new RuntimeException(
            'Seguridad: APP_ENV debe ser exactamente development.'
        );
    }

    if (!in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true)) {
        throw new RuntimeException(
            'Seguridad: solo se permite conectar a MySQL local.'
        );
    }

    if ($database !== STRESS_DATABASE) {
        throw new RuntimeException(
            'Seguridad: DB_DATABASE debe ser exactamente '
            . STRESS_DATABASE
            . '; no se modificarán otras bases.'
        );
    }

    $username = (string) ($_ENV['DB_USERNAME'] ?? '');
    $password = (string) ($_ENV['DB_PASSWORD'] ?? '');
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $host,
        $database
    );

    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

/**
 * @return list<int>
 */
function fetchIds(PDO $pdo, string $table, ?string $where = null): array
{
    $allowedTables = ['categorias', 'localidades', 'servicios'];

    if (!in_array($table, $allowedTables, true)) {
        throw new InvalidArgumentException('Tabla de referencia inválida.');
    }

    $sql = "SELECT id FROM `{$table}`";

    if ($where !== null) {
        $sql .= ' WHERE ' . $where;
    }

    $sql .= ' ORDER BY id';

    return array_map(
        'intval',
        $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN)
    );
}

/**
 * @return array<string, bool>
 */
function propertyColumns(PDO $pdo): array
{
    $columns = [];

    foreach ($pdo->query('SHOW COLUMNS FROM propiedades') as $column) {
        $columns[$column['Field']] = true;
    }

    return $columns;
}

function validateSchema(PDO $pdo): void
{
    $requiredTables = [
        'categorias',
        'consultas',
        'favoritos',
        'localidades',
        'mensajes_consultas',
        'propiedad_servicio',
        'propiedades',
        'reservas',
        'roles',
        'servicios',
        'usuarios',
    ];
    $statement = $pdo->query(
        'SELECT TABLE_NAME FROM information_schema.TABLES '
        . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = \'BASE TABLE\''
    );
    $availableTables = array_flip(
        $statement->fetchAll(PDO::FETCH_COLUMN)
    );
    $missingTables = array_values(array_diff(
        $requiredTables,
        array_keys($availableTables)
    ));

    if ($missingTables !== []) {
        throw new RuntimeException(
            'Faltan tablas requeridas: ' . implode(', ', $missingTables)
            . '. No se insertaron datos.'
        );
    }

    $requiredColumns = [
        'usuarios' => [
            'nombre',
            'apellido',
            'email',
            'telefono',
            'domicilio',
            'contrasena',
            'rol_id',
            'email_verified_at',
        ],
        'propiedades' => [
            'titulo',
            'descripcion',
            'precio',
            'expensas',
            'direccion',
            'cantidad_ambientes',
            'cantidad_dormitorios',
            'cantidad_banos',
            'capacidad',
            'disponible',
            'destacada',
            'categoria_id',
            'usuario_id',
            'localidad_id',
        ],
        'propiedad_servicio' => ['propiedad_id', 'servicio_id'],
        'consultas' => [
            'propiedad_id',
            'usuario_id',
            'perfil_interesado',
            'fecha_consulta',
        ],
        'mensajes_consultas' => [
            'consulta_id',
            'usuario_id',
            'mensaje',
            'fecha_mensaje',
        ],
        'favoritos' => ['usuario_id', 'propiedad_id'],
        'reservas' => [
            'propiedad_id',
            'usuario_id',
            'fecha_inicio_alquiler',
            'fecha_fin_alquiler',
            'estado',
            'fecha_reserva',
        ],
    ];

    foreach ($requiredColumns as $table => $columns) {
        $actualColumns = [];

        foreach ($pdo->query("SHOW COLUMNS FROM `{$table}`") as $column) {
            $actualColumns[$column['Field']] = true;
        }

        $missingColumns = array_values(array_diff(
            $columns,
            array_keys($actualColumns)
        ));

        if ($missingColumns !== []) {
            throw new RuntimeException(
                "La tabla {$table} no tiene las columnas requeridas: "
                . implode(', ', $missingColumns)
                . '. No se insertaron datos.'
            );
        }
    }
}

/**
 * @return list<array{0: int, 1: int}>
 */
function syntheticUsers(PDO $pdo, string $kind): array
{
    $statement = $pdo->prepare(
        'SELECT id, email FROM usuarios WHERE email LIKE ? ORDER BY email'
    );
    $statement->execute([
        STRESS_USER_PREFIX . $kind . '-%@' . STRESS_EMAIL_DOMAIN,
    ]);

    $users = [];

    foreach ($statement->fetchAll() as $user) {
        if (preg_match(
            '/^stress-' . preg_quote($kind, '/') . '-(\d+)@'
                . preg_quote(STRESS_EMAIL_DOMAIN, '/') . '$/',
            $user['email'],
            $matches
        )) {
            $users[(int) $matches[1]] = (int) $user['id'];
        }
    }

    ksort($users);

    return array_map(
        static fn (int $index, int $id): array => [$index, $id],
        array_keys($users),
        array_values($users)
    );
}

/**
 * @return array<int, int>
 */
function syntheticProperties(PDO $pdo): array
{
    $statement = $pdo->prepare(
        'SELECT id, titulo FROM propiedades WHERE titulo LIKE ?'
    );
    $statement->execute([STRESS_PROPERTY_PREFIX . '%']);
    $properties = [];

    foreach ($statement->fetchAll() as $property) {
        if (preg_match(
            '/^' . preg_quote(STRESS_PROPERTY_PREFIX, '/') . '(\d{8})$/',
            $property['titulo'],
            $matches
        )) {
            $properties[(int) $matches[1]] = (int) $property['id'];
        }
    }

    ksort($properties);

    return $properties;
}

/**
 * Insert rows in bounded transactions, leaving completed batches available
 * for a safe idempotent rerun if a later batch fails.
 *
 * @param iterable<array<string, mixed>> $rows
 * @param list<string> $columns
 */
function insertRows(
    PDO $pdo,
    string $table,
    array $columns,
    iterable $rows,
    bool $execute,
    bool $ignoreDuplicates = false
): int {
    $allowedTables = [
        'usuarios',
        'propiedades',
        'propiedad_servicio',
        'consultas',
        'mensajes_consultas',
        'favoritos',
        'reservas',
    ];

    if (!in_array($table, $allowedTables, true)) {
        throw new InvalidArgumentException('Tabla de destino inválida.');
    }

    $quotedColumns = implode(
        ', ',
        array_map(static fn (string $column): string => "`{$column}`", $columns)
    );
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $insertVerb = $ignoreDuplicates ? 'INSERT IGNORE' : 'INSERT';
    $statement = $pdo->prepare(
        "{$insertVerb} INTO `{$table}` ({$quotedColumns}) VALUES ({$placeholders})"
    );
    $pending = [];
    $inserted = 0;

    foreach ($rows as $row) {
        $pending[] = array_map(
            static fn (string $column) => $row[$column] ?? null,
            $columns
        );

        if (count($pending) >= BATCH_SIZE) {
            $inserted += insertBatch($pdo, $statement, $pending, $execute);
            $pending = [];
        }
    }

    if ($pending !== []) {
        $inserted += insertBatch($pdo, $statement, $pending, $execute);
    }

    return $inserted;
}

/**
 * @param list<list<mixed>> $rows
 */
function insertBatch(
    PDO $pdo,
    PDOStatement $statement,
    array $rows,
    bool $execute
): int {
    if (!$execute) {
        return count($rows);
    }

    $pdo->beginTransaction();

    try {
        $inserted = 0;

        foreach ($rows as $row) {
            $statement->execute($row);
            $inserted += $statement->rowCount();
        }

        $pdo->commit();

        return $inserted;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
}

/**
 * @return array<string, int>
 */
function generateUsers(
    PDO $pdo,
    int $userCount,
    bool $execute
): array {
    $ownerCount = max(1, (int) ceil($userCount * 0.2));
    $passwordHash = password_hash(
        bin2hex(random_bytes(32)),
        PASSWORD_DEFAULT
    );

    if ($passwordHash === false) {
        throw new RuntimeException('No se pudo preparar la contraseña sintética.');
    }

    $existingStatement = $pdo->query(
        "SELECT email FROM usuarios WHERE email LIKE 'stress-%@synthetic.invalid'"
    );
    $existingEmails = array_fill_keys(
        $existingStatement->fetchAll(PDO::FETCH_COLUMN),
        true
    );

    $rows = (static function () use (
        $userCount,
        $ownerCount,
        $passwordHash,
        $existingEmails
    ): Generator {
        for ($index = 1; $index <= $userCount; $index++) {
            $kind = $index <= $ownerCount ? 'owner' : 'tenant';
            $emailIndex = $kind === 'owner'
                ? $index
                : $index - $ownerCount;
            $email = sprintf(
                'stress-%s-%06d@%s',
                $kind,
                $emailIndex,
                STRESS_EMAIL_DOMAIN
            );

            if (isset($existingEmails[$email])) {
                continue;
            }

            yield [
                'nombre' => $kind === 'owner' ? 'Propietario' : 'Inquilino',
                'apellido' => sprintf('Sintetico %06d', $index),
                'email' => $email,
                'telefono' => sprintf('+54911%08d', $index),
                'domicilio' => sprintf('Domicilio de prueba %06d', $index),
                'contrasena' => $passwordHash,
                'rol_id' => 1,
                'email_verified_at' => null,
            ];
        }
    })();

    return [
        'planned' => $userCount,
        'inserted' => insertRows(
            $pdo,
            'usuarios',
            [
                'nombre',
                'apellido',
                'email',
                'telefono',
                'domicilio',
                'contrasena',
                'rol_id',
                'email_verified_at',
            ],
            $rows,
            $execute
        ),
    ];
}

/**
 * @param list<int> $categoryIds
 * @param list<int> $localityIds
 * @param array<int, int> $ownersByIndex
 * @param array<string, bool> $availableColumns
 * @return array<string, int>
 */
function generateProperties(
    PDO $pdo,
    int $propertyCount,
    array $categoryIds,
    array $localityIds,
    array $ownersByIndex,
    array $availableColumns,
    bool $execute
): array {
    $existingProperties = syntheticProperties($pdo);
    $ownerIndexes = array_keys($ownersByIndex);
    $columns = [
        'titulo',
        'descripcion',
        'precio',
        'expensas',
        'direccion',
        'cantidad_ambientes',
        'cantidad_dormitorios',
        'cantidad_banos',
        'capacidad',
        'disponible',
        'destacada',
        'categoria_id',
        'usuario_id',
        'localidad_id',
    ];

    foreach (
        [
            'acepta_mascotas' => true,
            'acepta_hijos' => true,
            'requisitos_interesados' => json_encode(
                ['ingresos_comprobables'],
                JSON_THROW_ON_ERROR
            ),
            'fecha_publicacion' => date('Y-m-d H:i:s'),
        ] as $optionalColumn => $value
    ) {
        if (isset($availableColumns[$optionalColumn])) {
            $columns[] = $optionalColumn;
        }
    }

    $rows = (static function () use (
        $propertyCount,
        $existingProperties,
        $columns,
        $categoryIds,
        $localityIds,
        $ownersByIndex,
        $ownerIndexes,
        $availableColumns
    ): Generator {
        for ($index = 1; $index <= $propertyCount; $index++) {
            if (isset($existingProperties[$index])) {
                continue;
            }

            $ownerIndex = $ownerIndexes[($index - 1) % count($ownerIndexes)];
            $row = [
                'titulo' => sprintf('%s%08d', STRESS_PROPERTY_PREFIX, $index),
                'descripcion' => sprintf(
                    'Propiedad sintética para pruebas de carga. Registro %d.',
                    $index
                ),
                'precio' => 150000 + (($index * 7919) % 1400000),
                'expensas' => ($index % 4) * 12500,
                'direccion' => sprintf('Calle de prueba %d', $index),
                'cantidad_ambientes' => 1 + ($index % 6),
                'cantidad_dormitorios' => $index % 5,
                'cantidad_banos' => 1 + ($index % 3),
                'capacidad' => 1 + ($index % 8),
                'disponible' => $index % 10 === 0 ? 0 : 1,
                'destacada' => $index % 20 === 0 ? 1 : 0,
                'categoria_id' => $categoryIds[($index - 1) % count($categoryIds)],
                'usuario_id' => $ownersByIndex[$ownerIndex],
                'localidad_id' => $localityIds[($index - 1) % count($localityIds)],
            ];

            if (isset($availableColumns['acepta_mascotas'])) {
                $row['acepta_mascotas'] = $index % 3 === 0 ? 1 : 0;
            }

            if (isset($availableColumns['acepta_hijos'])) {
                $row['acepta_hijos'] = $index % 4 === 0 ? 1 : 0;
            }

            if (isset($availableColumns['requisitos_interesados'])) {
                $row['requisitos_interesados'] = json_encode(
                    ['ingresos_comprobables'],
                    JSON_THROW_ON_ERROR
                );
            }

            if (isset($availableColumns['fecha_publicacion'])) {
                $row['fecha_publicacion'] = date(
                    'Y-m-d H:i:s',
                    time() - (($index % 365) * 86400)
                );
            }

            yield array_intersect_key($row, array_flip($columns));
        }
    })();

    $inserted = insertRows(
        $pdo,
        'propiedades',
        $columns,
        $rows,
        $execute
    );

    $allSyntheticProperties = syntheticProperties($pdo);

    return [
        'planned' => $propertyCount,
        'inserted' => $inserted,
        'available_for_relationships' => count($allSyntheticProperties),
        'ids_by_index' => $allSyntheticProperties,
    ];
}

/**
 * @param array<int, int> $propertyIds
 * @param list<int> $serviceIds
 * @return array<string, int>
 */
function generatePropertyServices(
    PDO $pdo,
    int $propertyCount,
    array $propertyIds,
    array $serviceIds,
    bool $execute
): array {
    $rows = (static function () use (
        $propertyCount,
        $propertyIds,
        $serviceIds
    ): Generator {
        for ($index = 1; $index <= $propertyCount; $index++) {
            if (!isset($propertyIds[$index])) {
                continue;
            }

            $serviceCount = min(3 + ($index % 3), count($serviceIds));

            for ($offset = 0; $offset < $serviceCount; $offset++) {
                $serviceIndex = (($index + $offset * 7) % count($serviceIds));

                yield [
                    'propiedad_id' => $propertyIds[$index],
                    'servicio_id' => $serviceIds[$serviceIndex],
                ];
            }
        }
    })();

    return [
        'planned' => $propertyCount,
        'inserted' => insertRows(
            $pdo,
            'propiedad_servicio',
            ['propiedad_id', 'servicio_id'],
            $rows,
            $execute,
            true
        ),
    ];
}

/**
 * @param array<int, int> $propertyIds
 * @param list<array{0: int, 1: int}> $tenantUsers
 * @param array<int, int> $ownersByIndex
 * @return array<string, int>
 */
function generateConversations(
    PDO $pdo,
    int $consultationCount,
    int $propertyCount,
    array $propertyIds,
    array $tenantUsers,
    array $ownersByIndex,
    bool $execute
): array {
    if ($consultationCount === 0) {
        return ['planned_consultations' => 0, 'inserted_consultations' => 0, 'inserted_messages' => 0];
    }

    if ($propertyIds === [] || $tenantUsers === []) {
        throw new RuntimeException(
            'Se necesitan propiedades y usuarios sintéticos para generar consultas.'
        );
    }

    $existingPairs = [];
    $existingMessageMarkers = [];
    $existing = $pdo->query(
        "SELECT c.id, c.propiedad_id, c.usuario_id, m.mensaje
         FROM consultas c
         INNER JOIN propiedades p ON p.id = c.propiedad_id
         INNER JOIN usuarios u ON u.id = c.usuario_id
         LEFT JOIN mensajes_consultas m ON m.consulta_id = c.id
         WHERE p.titulo LIKE 'STRESS-DATA-%'
           AND u.email LIKE 'stress-tenant-%@synthetic.invalid'"
    );

    foreach ($existing->fetchAll() as $row) {
        $pair = $row['propiedad_id'] . ':' . $row['usuario_id'];
        $existingPairs[$pair] = true;

        if (
            is_string($row['mensaje'])
            && str_starts_with($row['mensaje'], 'STRESS-DATA:conversation:')
        ) {
            $existingMessageMarkers[$row['mensaje']] = true;
        }
    }

    $consultationRows = [];
    $messageRows = [];
    $ownerIndexes = array_keys($ownersByIndex);
    $tenantCount = count($tenantUsers);

    for ($index = 1; $index <= $consultationCount; $index++) {
        $propertyIndex = (($index * 7919) % $propertyCount) + 1;
        $tenantOffset = ($index * 13) % $tenantCount;
        $tenantId = $tenantUsers[$tenantOffset][1];
        $propertyId = $propertyIds[$propertyIndex] ?? null;

        if ($propertyId === null) {
            continue;
        }

        $pair = $propertyId . ':' . $tenantId;

        if (!isset($existingPairs[$pair])) {
            $consultationRows[] = [
                'propiedad_id' => $propertyId,
                'usuario_id' => $tenantId,
                'perfil_interesado' => json_encode([
                    'seed' => 'stress-test',
                    'synthetic_index' => $index,
                    'ocupantes' => 1 + ($index % 5),
                ], JSON_THROW_ON_ERROR),
                'fecha_consulta' => date(
                    'Y-m-d H:i:s',
                    time() - (($index % 180) * 86400)
                ),
            ];
            $existingPairs[$pair] = true;
        }

        $ownerIndex = $ownerIndexes[($propertyIndex - 1) % count($ownerIndexes)];
        $ownerId = $ownersByIndex[$ownerIndex];

        for ($messageNumber = 1; $messageNumber <= 2; $messageNumber++) {
            $marker = sprintf(
                'STRESS-DATA:conversation:%08d:%d',
                $index,
                $messageNumber
            );

            if (isset($existingMessageMarkers[$marker])) {
                continue;
            }

            $messageRows[] = [
                'consulta_index' => $index,
                'property_id' => $propertyId,
                'tenant_id' => $tenantId,
                'owner_id' => $ownerId,
                'message_number' => $messageNumber,
                'marker' => $marker,
            ];
            $existingMessageMarkers[$marker] = true;
        }
    }

    $insertedConsultations = insertRows(
        $pdo,
        'consultas',
        [
            'propiedad_id',
            'usuario_id',
            'perfil_interesado',
            'fecha_consulta',
        ],
        $consultationRows,
        $execute
    );

    $insertedMessages = 0;

    if ($messageRows !== []) {
        $conversationStatement = $pdo->query(
            "SELECT c.id, c.propiedad_id, c.usuario_id
             FROM consultas c
             INNER JOIN propiedades p ON p.id = c.propiedad_id
             INNER JOIN usuarios u ON u.id = c.usuario_id
             WHERE p.titulo LIKE 'STRESS-DATA-%'
               AND u.email LIKE 'stress-tenant-%@synthetic.invalid'
             ORDER BY c.id"
        );
        $conversationIds = [];

        foreach ($conversationStatement->fetchAll() as $conversation) {
            $conversationIds[
                $conversation['propiedad_id'] . ':' . $conversation['usuario_id']
            ] = (int) $conversation['id'];
        }

        $messageInsertRows = (static function () use (
            $messageRows,
            $conversationIds
        ): Generator {
            foreach ($messageRows as $message) {
                $pair = $message['property_id'] . ':' . $message['tenant_id'];
                $consultationId = $conversationIds[$pair] ?? null;

                if ($consultationId === null) {
                    continue;
                }

                yield [
                    'consulta_id' => $consultationId,
                    'usuario_id' => $message['message_number'] === 1
                        ? $message['tenant_id']
                        : $message['owner_id'],
                    'mensaje' => $message['marker']
                        . ' Mensaje sintético de prueba.',
                    'fecha_mensaje' => date(
                        'Y-m-d H:i:s',
                        time() - (($message['consulta_index'] % 30) * 86400)
                    ),
                ];
            }
        })();

        $insertedMessages = insertRows(
            $pdo,
            'mensajes_consultas',
            ['consulta_id', 'usuario_id', 'mensaje', 'fecha_mensaje'],
            $messageInsertRows,
            $execute
        );
    }

    return [
        'planned_consultations' => count($consultationRows),
        'inserted_consultations' => $insertedConsultations,
        'planned_messages' => count($messageRows),
        'inserted_messages' => $insertedMessages,
    ];
}

/**
 * @param array<int, int> $propertyIds
 * @param list<array{0: int, 1: int}> $tenantUsers
 * @return array<string, int>
 */
function generateFavorites(
    PDO $pdo,
    int $favoriteCount,
    int $propertyCount,
    array $propertyIds,
    array $tenantUsers,
    bool $execute
): array {
    if ($favoriteCount === 0) {
        return ['planned' => 0, 'inserted' => 0];
    }

    $rows = (static function () use (
        $favoriteCount,
        $propertyCount,
        $propertyIds,
        $tenantUsers
    ): Generator {
        $tenantCount = count($tenantUsers);

        for ($index = 0; $index < $favoriteCount; $index++) {
            $propertyIndex = (($index * 7919) % $propertyCount) + 1;
            $propertyId = $propertyIds[$propertyIndex] ?? null;

            if ($propertyId === null || $tenantCount === 0) {
                continue;
            }

            yield [
                'usuario_id' => $tenantUsers[($index * 17) % $tenantCount][1],
                'propiedad_id' => $propertyId,
            ];
        }
    })();

    return [
        'planned' => $favoriteCount,
        'inserted' => insertRows(
            $pdo,
            'favoritos',
            ['usuario_id', 'propiedad_id'],
            $rows,
            $execute,
            true
        ),
    ];
}

/**
 * @param array<int, int> $propertyIds
 * @param list<array{0: int, 1: int}> $tenantUsers
 * @return array<string, int>
 */
function generateReservations(
    PDO $pdo,
    int $reservationCount,
    int $propertyCount,
    array $propertyIds,
    array $tenantUsers,
    bool $execute
): array {
    if ($reservationCount === 0) {
        return ['planned' => 0, 'inserted' => 0];
    }

    $existing = $pdo->query(
        "SELECT r.propiedad_id, r.usuario_id
         FROM reservas r
         INNER JOIN propiedades p ON p.id = r.propiedad_id
         INNER JOIN usuarios u ON u.id = r.usuario_id
         WHERE p.titulo LIKE 'STRESS-DATA-%'
           AND u.email LIKE 'stress-tenant-%@synthetic.invalid'"
    );
    $existingPairs = [];

    foreach ($existing->fetchAll() as $row) {
        $existingPairs[$row['propiedad_id'] . ':' . $row['usuario_id']] = true;
    }

    $rows = (static function () use (
        $reservationCount,
        $propertyCount,
        $propertyIds,
        $tenantUsers,
        $existingPairs
    ): Generator {
        $tenantCount = count($tenantUsers);

        for ($index = 0; $index < $reservationCount; $index++) {
            $propertyIndex = (($index * 7919) % $propertyCount) + 1;
            $propertyId = $propertyIds[$propertyIndex] ?? null;

            if ($propertyId === null || $tenantCount === 0) {
                continue;
            }

            $tenantId = $tenantUsers[($index * 31) % $tenantCount][1];
            $pair = $propertyId . ':' . $tenantId;

            if (isset($existingPairs[$pair])) {
                continue;
            }
            $existingPairs[$pair] = true;

            $start = date(
                'Y-m-d',
                time() - ((30 + ($index % 300)) * 86400)
            );
            $end = date(
                'Y-m-d',
                strtotime($start . ' + 12 months')
            );

            yield [
                'propiedad_id' => $propertyId,
                'usuario_id' => $tenantId,
                'fecha_inicio_alquiler' => $start,
                'fecha_fin_alquiler' => $end,
                'estado' => $index % 4 === 0 ? 'finalizada' : 'rechazada',
                'fecha_reserva' => date('Y-m-d H:i:s'),
            ];
        }
    })();

    return [
        'planned' => $reservationCount,
        'inserted' => insertRows(
            $pdo,
            'reservas',
            [
                'propiedad_id',
                'usuario_id',
                'fecha_inicio_alquiler',
                'fecha_fin_alquiler',
                'estado',
                'fecha_reserva',
            ],
            $rows,
            $execute
        ),
    ];
}

try {
    $options = parseArguments(array_slice($argv, 1));
    $projectRoot = dirname(__DIR__);
    $pdo = connectToTestDatabase($projectRoot);
    $seedLockName = 'alquiler-stress-data-' . STRESS_DATABASE;
    $lockStatement = $pdo->prepare('SELECT GET_LOCK(?, 0)');
    $lockStatement->execute([$seedLockName]);
    $lockAcquired = (int) $lockStatement->fetchColumn() === 1;

    if (!$lockAcquired) {
        throw new RuntimeException(
            'Otra generación de datos está en curso para esta base. No se insertaron datos.'
        );
    }

    $selectedDatabase = $pdo->query('SELECT DATABASE()')->fetchColumn();

    if ($selectedDatabase !== STRESS_DATABASE) {
        throw new RuntimeException(
            'La conexión no corresponde a la base de datos de pruebas autorizada.'
        );
    }

    validateSchema($pdo);
    $categoryIds = fetchIds($pdo, 'categorias', 'deleted_at IS NULL');
    $localityIds = fetchIds($pdo, 'localidades', 'deleted_at IS NULL');
    $serviceIds = fetchIds($pdo, 'servicios', 'deleted_at IS NULL');
    $roleStatement = $pdo->prepare(
        "SELECT id FROM roles WHERE nombre = 'usuario' AND deleted_at IS NULL LIMIT 1"
    );
    $roleStatement->execute();
    $userRoleId = $roleStatement->fetchColumn();

    if (
        $categoryIds === []
        || $localityIds === []
        || $serviceIds === []
        || $userRoleId === false
    ) {
        throw new RuntimeException(
            'Faltan datos base activos (rol usuario, categorías, localidades o servicios). '
            . 'Importe y prepare el esquema de pruebas antes de cargar datos.'
        );
    }

    if ((int) $userRoleId !== 1) {
        throw new RuntimeException(
            'El rol activo "usuario" no tiene ID 1; se cancela para no asignar permisos incorrectos.'
        );
    }

    $counts = [
        'users' => (int) $options['users'],
        'properties' => (int) $options['properties'],
        'consultations' => (int) $options['consultations'],
        'messages' => (int) $options['consultations'] * 2,
        'favorites' => (int) $options['favorites'],
        'reservations' => (int) $options['reservations'],
        'property_services' => (int) $options['properties'] * 4,
        'property_images' => 0,
    ];

    echo 'Base objetivo: ' . $selectedDatabase . PHP_EOL;
    echo 'Modo: ' . ($options['execute'] ? 'ESCRITURA' : 'SIMULACIÓN')
        . PHP_EOL;
    echo 'Filas planificadas (aproximadas; no se incluyen ya existentes):'
        . PHP_EOL;

    foreach ($counts as $table => $count) {
        printf("  %-20s %d" . PHP_EOL, $table, $count);
    }

    echo 'No se borrará ni truncará ninguna tabla.' . PHP_EOL;

    if (!$options['execute']) {
        $pdo->query(
            'SELECT RELEASE_LOCK(' . $pdo->quote($seedLockName) . ')'
        );
        $lockAcquired = false;
        echo 'Simulación finalizada. Revise el plan; use --execute para insertar.'
            . PHP_EOL;
        exit(0);
    }

    $startedAt = microtime(true);
    $userSummary = generateUsers(
        $pdo,
        (int) $options['users'],
        true
    );
    $allUsers = syntheticUsers($pdo, 'owner');
    $tenantUsers = syntheticUsers($pdo, 'tenant');

    if (count($allUsers) === 0 || count($tenantUsers) === 0) {
        throw new RuntimeException(
            'No se recuperaron los usuarios sintéticos después de insertarlos.'
        );
    }

    $ownersByIndex = array_column($allUsers, 1, 0);
    $propertyColumns = propertyColumns($pdo);
    $propertySummary = generateProperties(
        $pdo,
        (int) $options['properties'],
        $categoryIds,
        $localityIds,
        $ownersByIndex,
        $propertyColumns,
        true
    );
    $propertyIds = $propertySummary['ids_by_index'];
    $serviceSummary = generatePropertyServices(
        $pdo,
        (int) $options['properties'],
        $propertyIds,
        $serviceIds,
        true
    );
    $conversationSummary = generateConversations(
        $pdo,
        (int) $options['consultations'],
        (int) $options['properties'],
        $propertyIds,
        $tenantUsers,
        $ownersByIndex,
        true
    );
    $favoriteSummary = generateFavorites(
        $pdo,
        (int) $options['favorites'],
        (int) $options['properties'],
        $propertyIds,
        $tenantUsers,
        true
    );
    $reservationSummary = generateReservations(
        $pdo,
        (int) $options['reservations'],
        (int) $options['properties'],
        $propertyIds,
        $tenantUsers,
        true
    );

    echo PHP_EOL . 'Carga completada:' . PHP_EOL;
    printf("  Usuarios insertados: %d / %d" . PHP_EOL, $userSummary['inserted'], $userSummary['planned']);
    printf("  Propiedades insertadas: %d / %d" . PHP_EOL, $propertySummary['inserted'], $propertySummary['planned']);
    printf("  Relaciones propiedad-servicio nuevas: %d" . PHP_EOL, $serviceSummary['inserted']);
    printf("  Consultas nuevas: %d; mensajes nuevos: %d" . PHP_EOL, $conversationSummary['inserted_consultations'], $conversationSummary['inserted_messages']);
    printf("  Favoritos nuevos: %d" . PHP_EOL, $favoriteSummary['inserted']);
    printf("  Reservas nuevas: %d" . PHP_EOL, $reservationSummary['inserted']);
    printf("  Duración: %.2f segundos" . PHP_EOL, microtime(true) - $startedAt);
    echo 'Puede volver a ejecutar el script: salta los datos sintéticos ya creados.'
        . PHP_EOL;

    $releaseStatement = $pdo->prepare('SELECT RELEASE_LOCK(?)');
    $releaseStatement->execute([$seedLockName]);
    $lockAcquired = false;

    exit(0);
} catch (Throwable $exception) {
    if (isset($pdo, $lockAcquired, $seedLockName) && $lockAcquired) {
        try {
            $releaseStatement = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            $releaseStatement->execute([$seedLockName]);
        } catch (Throwable $releaseException) {
            fwrite(
                STDERR,
                'Advertencia: no se pudo liberar el bloqueo de carga: '
                . $releaseException->getMessage()
                . PHP_EOL
            );
        }
    }

    fwrite(STDERR, 'Error: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
