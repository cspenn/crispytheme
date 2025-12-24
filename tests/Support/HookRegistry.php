<?php
/**
 * Hook Registry
 *
 * Tracks WordPress hooks registered during tests for assertions.
 *
 * @package CrispyTheme\Tests\Support
 */

declare(strict_types=1);

namespace CrispyTheme\Tests\Support;

/**
 * Track WordPress hooks registered during tests.
 */
class HookRegistry
{
    /**
     * Registered actions.
     *
     * @var array<string, array<int, array{callback: callable|array|string, priority: int, accepted_args: int}>>
     */
    private array $actions = [];

    /**
     * Registered filters.
     *
     * @var array<string, array<int, array{callback: callable|array|string, priority: int, accepted_args: int}>>
     */
    private array $filters = [];

    /**
     * Action execution log.
     *
     * @var array<string, array<int, array{args: array<mixed>, timestamp: int}>>
     */
    private array $actionExecutions = [];

    /**
     * Filter execution log.
     *
     * @var array<string, array<int, array{input: mixed, output: mixed, timestamp: int}>>
     */
    private array $filterExecutions = [];

    /**
     * Register an action.
     *
     * @param string                   $hook          Hook name.
     * @param callable|array<mixed>|string $callback  Callback function.
     * @param int                      $priority      Priority.
     * @param int                      $accepted_args Number of accepted arguments.
     * @return bool True on success.
     */
    public function registerAction(string $hook, callable|array|string $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        if (!isset($this->actions[$hook])) {
            $this->actions[$hook] = [];
        }

        $this->actions[$hook][] = [
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        ];

        return true;
    }

    /**
     * Register a filter.
     *
     * @param string                   $hook          Hook name.
     * @param callable|array<mixed>|string $callback  Callback function.
     * @param int                      $priority      Priority.
     * @param int                      $accepted_args Number of accepted arguments.
     * @return bool True on success.
     */
    public function registerFilter(string $hook, callable|array|string $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        if (!isset($this->filters[$hook])) {
            $this->filters[$hook] = [];
        }

        $this->filters[$hook][] = [
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        ];

        return true;
    }

    /**
     * Record action execution.
     *
     * @param string        $hook Hook name.
     * @param array<mixed>  $args Arguments passed.
     * @return void
     */
    public function recordActionExecution(string $hook, array $args = []): void
    {
        if (!isset($this->actionExecutions[$hook])) {
            $this->actionExecutions[$hook] = [];
        }

        $this->actionExecutions[$hook][] = [
            'args'      => $args,
            'timestamp' => time(),
        ];
    }

    /**
     * Record filter execution.
     *
     * @param string $hook   Hook name.
     * @param mixed  $input  Input value.
     * @param mixed  $output Output value.
     * @return void
     */
    public function recordFilterExecution(string $hook, mixed $input, mixed $output): void
    {
        if (!isset($this->filterExecutions[$hook])) {
            $this->filterExecutions[$hook] = [];
        }

        $this->filterExecutions[$hook][] = [
            'input'     => $input,
            'output'    => $output,
            'timestamp' => time(),
        ];
    }

    /**
     * Check if an action is registered.
     *
     * @param string                        $hook     Hook name.
     * @param callable|array<mixed>|string|null $callback Optional specific callback.
     * @return bool True if registered.
     */
    public function hasAction(string $hook, callable|array|string|null $callback = null): bool
    {
        if (!isset($this->actions[$hook])) {
            return false;
        }

        if ($callback === null) {
            return true;
        }

        foreach ($this->actions[$hook] as $registered) {
            if ($this->callbacksMatch($registered['callback'], $callback)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a filter is registered.
     *
     * @param string                        $hook     Hook name.
     * @param callable|array<mixed>|string|null $callback Optional specific callback.
     * @return bool True if registered.
     */
    public function hasFilter(string $hook, callable|array|string|null $callback = null): bool
    {
        if (!isset($this->filters[$hook])) {
            return false;
        }

        if ($callback === null) {
            return true;
        }

        foreach ($this->filters[$hook] as $registered) {
            if ($this->callbacksMatch($registered['callback'], $callback)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get action execution count.
     *
     * @param string $hook Hook name.
     * @return int Execution count.
     */
    public function getActionExecutionCount(string $hook): int
    {
        return isset($this->actionExecutions[$hook]) ? count($this->actionExecutions[$hook]) : 0;
    }

    /**
     * Get filter execution count.
     *
     * @param string $hook Hook name.
     * @return int Execution count.
     */
    public function getFilterExecutionCount(string $hook): int
    {
        return isset($this->filterExecutions[$hook]) ? count($this->filterExecutions[$hook]) : 0;
    }

    /**
     * Get all registered actions for a hook.
     *
     * @param string $hook Hook name.
     * @return array<int, array{callback: callable|array|string, priority: int, accepted_args: int}>
     */
    public function getActions(string $hook): array
    {
        return $this->actions[$hook] ?? [];
    }

    /**
     * Get all registered filters for a hook.
     *
     * @param string $hook Hook name.
     * @return array<int, array{callback: callable|array|string, priority: int, accepted_args: int}>
     */
    public function getFilters(string $hook): array
    {
        return $this->filters[$hook] ?? [];
    }

    /**
     * Get action execution log for a hook.
     *
     * @param string $hook Hook name.
     * @return array<int, array{args: array<mixed>, timestamp: int}>
     */
    public function getActionExecutions(string $hook): array
    {
        return $this->actionExecutions[$hook] ?? [];
    }

    /**
     * Get filter execution log for a hook.
     *
     * @param string $hook Hook name.
     * @return array<int, array{input: mixed, output: mixed, timestamp: int}>
     */
    public function getFilterExecutions(string $hook): array
    {
        return $this->filterExecutions[$hook] ?? [];
    }

    /**
     * Reset all registered hooks and executions.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->actions          = [];
        $this->filters          = [];
        $this->actionExecutions = [];
        $this->filterExecutions = [];
    }

    /**
     * Check if two callbacks match.
     *
     * @param callable|array<mixed>|string $a First callback.
     * @param callable|array<mixed>|string $b Second callback.
     * @return bool True if they match.
     */
    private function callbacksMatch(callable|array|string $a, callable|array|string $b): bool
    {
        // Simple string comparison for function names.
        if (is_string($a) && is_string($b)) {
            return $a === $b;
        }

        // Array comparison for class methods.
        if (is_array($a) && is_array($b)) {
            return $a === $b;
        }

        // For closures, we can only compare by identity.
        return $a === $b;
    }
}
