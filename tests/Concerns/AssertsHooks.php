<?php
/**
 * Asserts Hooks Trait
 *
 * Provides WordPress hook assertions for tests.
 *
 * @package CrispyTheme\Tests\Concerns
 */

declare(strict_types=1);

namespace CrispyTheme\Tests\Concerns;

/**
 * Trait for WordPress hook assertions.
 *
 * Note: This trait expects MocksWordPressHooks to also be used in the test.
 */
trait AssertsHooks
{
    /**
     * Assert an action is registered.
     *
     * @param string                        $hook     Hook name.
     * @param callable|array<mixed>|string|null $callback Optional specific callback.
     * @param int|null                      $priority Optional priority check.
     * @return void
     */
    protected function assertActionRegistered(
        string $hook,
        callable|array|string|null $callback = null,
        ?int $priority = null
    ): void {
        expect($this->hookRegistry->hasAction($hook, $callback))->toBeTrue(
            "Expected action '{$hook}' to be registered"
        );

        if ($priority !== null && $callback !== null) {
            $actions = $this->hookRegistry->getActions($hook);
            $found   = false;
            foreach ($actions as $action) {
                if ($this->matchesCallback($action['callback'], $callback)) {
                    expect($action['priority'])->toBe($priority);
                    $found = true;
                    break;
                }
            }
            expect($found)->toBeTrue("Action with specified callback not found");
        }
    }

    /**
     * Assert an action is not registered.
     *
     * @param string                        $hook     Hook name.
     * @param callable|array<mixed>|string|null $callback Optional specific callback.
     * @return void
     */
    protected function assertActionNotRegistered(
        string $hook,
        callable|array|string|null $callback = null
    ): void {
        expect($this->hookRegistry->hasAction($hook, $callback))->toBeFalse(
            "Expected action '{$hook}' to not be registered"
        );
    }

    /**
     * Assert a filter is registered.
     *
     * @param string                        $hook     Hook name.
     * @param callable|array<mixed>|string|null $callback Optional specific callback.
     * @param int|null                      $priority Optional priority check.
     * @return void
     */
    protected function assertFilterRegistered(
        string $hook,
        callable|array|string|null $callback = null,
        ?int $priority = null
    ): void {
        expect($this->hookRegistry->hasFilter($hook, $callback))->toBeTrue(
            "Expected filter '{$hook}' to be registered"
        );

        if ($priority !== null && $callback !== null) {
            $filters = $this->hookRegistry->getFilters($hook);
            $found   = false;
            foreach ($filters as $filter) {
                if ($this->matchesCallback($filter['callback'], $callback)) {
                    expect($filter['priority'])->toBe($priority);
                    $found = true;
                    break;
                }
            }
            expect($found)->toBeTrue("Filter with specified callback not found");
        }
    }

    /**
     * Assert a filter is not registered.
     *
     * @param string                        $hook     Hook name.
     * @param callable|array<mixed>|string|null $callback Optional specific callback.
     * @return void
     */
    protected function assertFilterNotRegistered(
        string $hook,
        callable|array|string|null $callback = null
    ): void {
        expect($this->hookRegistry->hasFilter($hook, $callback))->toBeFalse(
            "Expected filter '{$hook}' to not be registered"
        );
    }

    /**
     * Assert an action was executed.
     *
     * @param string $hook  Hook name.
     * @param int    $times Expected execution count.
     * @return void
     */
    protected function assertActionFired(string $hook, int $times = 1): void
    {
        $count = $this->hookRegistry->getActionExecutionCount($hook);
        expect($count)->toBe($times);
    }

    /**
     * Assert an action was not executed.
     *
     * @param string $hook Hook name.
     * @return void
     */
    protected function assertActionNotFired(string $hook): void
    {
        $count = $this->hookRegistry->getActionExecutionCount($hook);
        expect($count)->toBe(0);
    }

    /**
     * Assert a filter was applied.
     *
     * @param string $hook  Hook name.
     * @param int    $times Expected execution count.
     * @return void
     */
    protected function assertFilterApplied(string $hook, int $times = 1): void
    {
        $count = $this->hookRegistry->getFilterExecutionCount($hook);
        expect($count)->toBe($times);
    }

    /**
     * Assert a filter was not applied.
     *
     * @param string $hook Hook name.
     * @return void
     */
    protected function assertFilterNotApplied(string $hook): void
    {
        $count = $this->hookRegistry->getFilterExecutionCount($hook);
        expect($count)->toBe(0);
    }

    /**
     * Assert filter returns expected value.
     *
     * @param string $hook     Filter hook name.
     * @param mixed  $input    Input value.
     * @param mixed  $expected Expected output value.
     * @return void
     */
    protected function assertFilterReturns(string $hook, mixed $input, mixed $expected): void
    {
        // This requires apply_filters to be mocked to execute registered callbacks.
        $filters = $this->hookRegistry->getFilters($hook);

        $value = $input;
        foreach ($filters as $filter) {
            if (is_callable($filter['callback'])) {
                $value = call_user_func($filter['callback'], $value);
            }
        }

        expect($value)->toBe($expected);
    }

    /**
     * Get action execution arguments.
     *
     * @param string $hook Hook name.
     * @param int    $call Which call to get (0-indexed).
     * @return array<mixed> Arguments passed to action.
     */
    protected function getActionArgs(string $hook, int $call = 0): array
    {
        $executions = $this->hookRegistry->getActionExecutions($hook);
        return $executions[$call]['args'] ?? [];
    }

    /**
     * Get filter execution input/output.
     *
     * @param string $hook Hook name.
     * @param int    $call Which call to get (0-indexed).
     * @return array{input: mixed, output: mixed}|null Execution data.
     */
    protected function getFilterExecution(string $hook, int $call = 0): ?array
    {
        $executions = $this->hookRegistry->getFilterExecutions($hook);
        if (!isset($executions[$call])) {
            return null;
        }
        return [
            'input'  => $executions[$call]['input'],
            'output' => $executions[$call]['output'],
        ];
    }

    /**
     * Check if callbacks match.
     *
     * @param callable|array<mixed>|string $a First callback.
     * @param callable|array<mixed>|string $b Second callback.
     * @return bool True if they match.
     */
    private function matchesCallback(callable|array|string $a, callable|array|string $b): bool
    {
        if (is_string($a) && is_string($b)) {
            return $a === $b;
        }

        if (is_array($a) && is_array($b)) {
            return $a === $b;
        }

        return $a === $b;
    }
}
