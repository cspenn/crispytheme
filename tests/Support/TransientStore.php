<?php
/**
 * In-Memory Transient Store
 *
 * Provides in-memory transient storage for testing WordPress transient operations.
 *
 * @package CrispyTheme\Tests\Support
 */

declare(strict_types=1);

namespace CrispyTheme\Tests\Support;

/**
 * In-memory transient storage for testing.
 */
class TransientStore
{
    /**
     * Stored transients.
     *
     * @var array<string, array{value: mixed, expiration: int, created_at: int}>
     */
    private array $transients = [];

    /**
     * Operation log for assertions.
     *
     * @var array<int, array{operation: string, key: string, value?: mixed, expiration?: int, timestamp: int}>
     */
    private array $operations = [];

    /**
     * Get a transient value.
     *
     * @param string $name Transient name.
     * @return mixed Transient value or false if not found/expired.
     */
    public function get(string $name): mixed
    {
        $this->logOperation('get', $name);

        if (!$this->has($name)) {
            return false;
        }

        $transient = $this->transients[$name];

        // Check expiration (0 means no expiration).
        if ($transient['expiration'] > 0) {
            $expires_at = $transient['created_at'] + $transient['expiration'];
            if (time() > $expires_at) {
                $this->delete($name);
                return false;
            }
        }

        return $transient['value'];
    }

    /**
     * Set a transient value.
     *
     * @param string $name       Transient name.
     * @param mixed  $value      Transient value.
     * @param int    $expiration Expiration in seconds (0 = no expiration).
     * @return bool True on success.
     */
    public function set(string $name, mixed $value, int $expiration = 0): bool
    {
        $this->logOperation('set', $name, $value, $expiration);

        $this->transients[$name] = [
            'value'      => $value,
            'expiration' => $expiration,
            'created_at' => time(),
        ];

        return true;
    }

    /**
     * Delete a transient.
     *
     * @param string $name Transient name.
     * @return bool True if deleted, false if not found.
     */
    public function delete(string $name): bool
    {
        $this->logOperation('delete', $name);

        if (!isset($this->transients[$name])) {
            return false;
        }

        unset($this->transients[$name]);
        return true;
    }

    /**
     * Check if a transient exists (regardless of expiration).
     *
     * @param string $name Transient name.
     * @return bool True if exists.
     */
    public function has(string $name): bool
    {
        return isset($this->transients[$name]);
    }

    /**
     * Clear all transients.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->transients = [];
        $this->operations = [];
    }

    /**
     * Get all operations performed.
     *
     * @return array<int, array{operation: string, key: string, value?: mixed, expiration?: int, timestamp: int}>
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * Get operations of a specific type.
     *
     * @param string $operation Operation type ('get', 'set', 'delete').
     * @return array<int, array{operation: string, key: string, value?: mixed, expiration?: int, timestamp: int}>
     */
    public function getOperationsOfType(string $operation): array
    {
        return array_filter(
            $this->operations,
            static fn(array $op): bool => $op['operation'] === $operation
        );
    }

    /**
     * Get the count of operations for a specific key.
     *
     * @param string      $key       Transient key.
     * @param string|null $operation Optional operation type filter.
     * @return int Number of operations.
     */
    public function getOperationCount(string $key, ?string $operation = null): int
    {
        $count = 0;
        foreach ($this->operations as $op) {
            if ($op['key'] === $key) {
                if ($operation === null || $op['operation'] === $operation) {
                    ++$count;
                }
            }
        }
        return $count;
    }

    /**
     * Get the last expiration value set for a transient.
     *
     * @param string $name Transient name.
     * @return int|null Expiration value or null if never set.
     */
    public function getLastExpiration(string $name): ?int
    {
        if (!isset($this->transients[$name])) {
            return null;
        }
        return $this->transients[$name]['expiration'];
    }

    /**
     * Get all transient keys.
     *
     * @return array<string> List of transient keys.
     */
    public function getKeys(): array
    {
        return array_keys($this->transients);
    }

    /**
     * Get all transients matching a pattern.
     *
     * @param string $pattern Regex pattern to match keys.
     * @return array<string, mixed> Matching transients.
     */
    public function getMatching(string $pattern): array
    {
        $matches = [];
        foreach ($this->transients as $key => $data) {
            if (preg_match($pattern, $key)) {
                $matches[$key] = $data['value'];
            }
        }
        return $matches;
    }

    /**
     * Log an operation.
     *
     * @param string     $operation  Operation type.
     * @param string     $key        Transient key.
     * @param mixed|null $value      Value (for set operations).
     * @param int|null   $expiration Expiration (for set operations).
     */
    private function logOperation(string $operation, string $key, mixed $value = null, ?int $expiration = null): void
    {
        $entry = [
            'operation' => $operation,
            'key'       => $key,
            'timestamp' => time(),
        ];

        if ($operation === 'set') {
            $entry['value']      = $value;
            $entry['expiration'] = $expiration;
        }

        $this->operations[] = $entry;
    }
}
