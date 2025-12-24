<?php
/**
 * Mocks WordPress Hooks Trait
 *
 * Provides WordPress hook mocking for tests.
 *
 * @package CrispyTheme\Tests\Concerns
 */

declare(strict_types=1);

namespace CrispyTheme\Tests\Concerns;

use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use CrispyTheme\Tests\Support\HookRegistry;

/**
 * Trait for WordPress hook mocking.
 */
trait MocksWordPressHooks
{
    /**
     * Hook registry instance.
     */
    protected HookRegistry $hookRegistry;

    /**
     * Custom filter callbacks for apply_filters.
     *
     * @var array<string, callable>
     */
    protected array $filterCallbacks = [];

    /**
     * Custom action callbacks for do_action.
     *
     * @var array<string, callable>
     */
    protected array $actionCallbacks = [];

    /**
     * Set up hook mocking.
     *
     * @return void
     */
    protected function setupHooks(): void
    {
        $this->hookRegistry    = new HookRegistry();
        $this->filterCallbacks = [];
        $this->actionCallbacks = [];

        $this->mockAddAction();
        $this->mockAddFilter();
        $this->mockRemoveAction();
        $this->mockRemoveFilter();
    }

    /**
     * Mock add_action.
     *
     * @return void
     */
    protected function mockAddAction(): void
    {
        Functions\when('add_action')->alias(
            function (string $hook, callable|array|string $callback, int $priority = 10, int $accepted_args = 1): bool {
                return $this->hookRegistry->registerAction($hook, $callback, $priority, $accepted_args);
            }
        );
    }

    /**
     * Mock add_filter.
     *
     * @return void
     */
    protected function mockAddFilter(): void
    {
        Functions\when('add_filter')->alias(
            function (string $hook, callable|array|string $callback, int $priority = 10, int $accepted_args = 1): bool {
                return $this->hookRegistry->registerFilter($hook, $callback, $priority, $accepted_args);
            }
        );
    }

    /**
     * Mock remove_action.
     *
     * @return void
     */
    protected function mockRemoveAction(): void
    {
        Functions\when('remove_action')->justReturn(true);
    }

    /**
     * Mock remove_filter.
     *
     * @return void
     */
    protected function mockRemoveFilter(): void
    {
        Functions\when('remove_filter')->justReturn(true);
    }

    /**
     * Mock apply_filters to pass through value.
     *
     * @return void
     */
    protected function mockApplyFiltersPassthrough(): void
    {
        Functions\when('apply_filters')->alias(
            function (string $hook, mixed $value, mixed ...$args): mixed {
                $this->hookRegistry->recordFilterExecution($hook, $value, $value);

                // Check for custom callback.
                if (isset($this->filterCallbacks[$hook])) {
                    $result = ($this->filterCallbacks[$hook])($value, ...$args);
                    $this->hookRegistry->recordFilterExecution($hook, $value, $result);
                    return $result;
                }

                return $value;
            }
        );
    }

    /**
     * Mock apply_filters to return second argument (common pattern).
     *
     * @return void
     */
    protected function mockApplyFiltersReturnArg2(): void
    {
        Functions\when('apply_filters')->returnArg(2);
    }

    /**
     * Register a custom filter callback.
     *
     * @param string   $hook     Filter hook name.
     * @param callable $callback Callback function.
     * @return void
     */
    protected function registerFilterCallback(string $hook, callable $callback): void
    {
        $this->filterCallbacks[$hook] = $callback;
    }

    /**
     * Mock a specific filter to return a value.
     *
     * @param string $hook  Filter hook name.
     * @param mixed  $value Value to return.
     * @return void
     */
    protected function mockFilterReturn(string $hook, mixed $value): void
    {
        $this->filterCallbacks[$hook] = static fn(): mixed => $value;
    }

    /**
     * Mock do_action.
     *
     * @return void
     */
    protected function mockDoAction(): void
    {
        Functions\when('do_action')->alias(
            function (string $hook, mixed ...$args): void {
                $this->hookRegistry->recordActionExecution($hook, $args);

                // Execute custom callback if registered.
                if (isset($this->actionCallbacks[$hook])) {
                    ($this->actionCallbacks[$hook])(...$args);
                }
            }
        );
    }

    /**
     * Register a custom action callback.
     *
     * @param string   $hook     Action hook name.
     * @param callable $callback Callback function.
     * @return void
     */
    protected function registerActionCallback(string $hook, callable $callback): void
    {
        $this->actionCallbacks[$hook] = $callback;
    }

    /**
     * Mock has_action.
     *
     * @return void
     */
    protected function mockHasAction(): void
    {
        Functions\when('has_action')->alias(
            fn(string $hook, callable|array|string|null $callback = null): bool => $this->hookRegistry->hasAction($hook, $callback)
        );
    }

    /**
     * Mock has_filter.
     *
     * @return void
     */
    protected function mockHasFilter(): void
    {
        Functions\when('has_filter')->alias(
            fn(string $hook, callable|array|string|null $callback = null): bool => $this->hookRegistry->hasFilter($hook, $callback)
        );
    }

    /**
     * Get registered actions for a hook.
     *
     * @param string $hook Hook name.
     * @return array<int, array{callback: callable|array|string, priority: int, accepted_args: int}>
     */
    protected function getRegisteredActions(string $hook): array
    {
        return $this->hookRegistry->getActions($hook);
    }

    /**
     * Get registered filters for a hook.
     *
     * @param string $hook Hook name.
     * @return array<int, array{callback: callable|array|string, priority: int, accepted_args: int}>
     */
    protected function getRegisteredFilters(string $hook): array
    {
        return $this->hookRegistry->getFilters($hook);
    }

    /**
     * Get action execution count.
     *
     * @param string $hook Hook name.
     * @return int Execution count.
     */
    protected function getActionExecutionCount(string $hook): int
    {
        return $this->hookRegistry->getActionExecutionCount($hook);
    }

    /**
     * Get filter execution count.
     *
     * @param string $hook Hook name.
     * @return int Execution count.
     */
    protected function getFilterExecutionCount(string $hook): int
    {
        return $this->hookRegistry->getFilterExecutionCount($hook);
    }

    /**
     * Reset hook registry.
     *
     * @return void
     */
    protected function resetHooks(): void
    {
        $this->hookRegistry->reset();
        $this->filterCallbacks = [];
        $this->actionCallbacks = [];
    }
}
