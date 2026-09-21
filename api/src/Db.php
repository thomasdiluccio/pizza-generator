<?php

declare(strict_types=1);

namespace Pizza;

/**
 * Stores baked pizzas. PostgreSQL when Upsun hands us a relationship,
 * SQLite on a laptop, and an in-memory list if neither is writable.
 */
final class Db
{
    private ?\PDO $pdo = null;
    private array $memory = [];

    public function __construct()
    {
        try {
            $this->pdo = $this->connect();
            $this->migrate();
        } catch (\Throwable) {
            $this->pdo = null; // Degrade to memory rather than 500 on a demo.
        }
    }

    public function driver(): string
    {
        return $this->pdo?->getAttribute(\PDO::ATTR_DRIVER_NAME) ?? 'memory';
    }

    /** @param array<string, mixed> $pizza */
    public function save(array $pizza): array
    {
        $pizza['id'] = bin2hex(random_bytes(6));
        $pizza['createdAt'] = gmdate('c');

        if ($this->pdo === null) {
            array_unshift($this->memory, $pizza);
            $this->memory = array_slice($this->memory, 0, 25);

            return $pizza;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO pizzas (id, chef, name, score, total, payload, created_at) '
            . 'VALUES (:id, :chef, :name, :score, :total, :payload, :created_at)'
        );
        $stmt->execute([
            'id' => $pizza['id'],
            'chef' => $pizza['chef'],
            'name' => $pizza['name'],
            'score' => $pizza['verdict']['score'],
            'total' => $pizza['price']['total'],
            'payload' => json_encode($pizza, JSON_THROW_ON_ERROR),
            'created_at' => $pizza['createdAt'],
        ]);

        return $pizza;
    }

    /** @return list<array<string, mixed>> */
    public function recent(int $limit = 12): array
    {
        if ($this->pdo === null) {
            return array_slice($this->memory, 0, $limit);
        }

        $stmt = $this->pdo->prepare('SELECT payload FROM pizzas ORDER BY created_at DESC, id DESC LIMIT :limit');
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            static fn (string $row): array => json_decode($row, true, 512, JSON_THROW_ON_ERROR),
            $stmt->fetchAll(\PDO::FETCH_COLUMN),
        );
    }

    private function connect(): \PDO
    {
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ];

        if ($relationship = $this->upsunRelationship()) {
            $dsn = sprintf(
                'pgsql:host=%s;port=%d;dbname=%s',
                $relationship['host'],
                $relationship['port'],
                $relationship['path'],
            );

            return new \PDO($dsn, $relationship['username'], $relationship['password'], $options);
        }

        $path = getenv('SQLITE_PATH') ?: sys_get_temp_dir() . '/pizza-generator.sqlite';

        return new \PDO('sqlite:' . $path, null, null, $options);
    }

    /** @return array<string, mixed>|null */
    private function upsunRelationship(): ?array
    {
        // Upsun exposes service credentials as base64-encoded JSON.
        $raw = getenv('PLATFORM_RELATIONSHIPS');
        if ($raw === false || $raw === '') {
            return null;
        }

        $relationships = json_decode(base64_decode($raw, true) ?: '[]', true);
        foreach (['postgresql', 'database', 'pizzadb'] as $name) {
            if (!empty($relationships[$name][0])) {
                return $relationships[$name][0];
            }
        }

        return null;
    }

    private function migrate(): void
    {
        $this->pdo?->exec(
            'CREATE TABLE IF NOT EXISTS pizzas ('
            . 'id VARCHAR(32) PRIMARY KEY, '
            . 'chef VARCHAR(64) NOT NULL, '
            . 'name VARCHAR(128) NOT NULL, '
            . 'score INTEGER NOT NULL, '
            . 'total NUMERIC(6,2) NOT NULL, '
            . 'payload TEXT NOT NULL, '
            . 'created_at VARCHAR(32) NOT NULL)'
        );
    }
}
