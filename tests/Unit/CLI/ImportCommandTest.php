<?php
/**
 * ImportCommand Unit Tests
 *
 * @package CrispyTheme\Tests\Unit\CLI
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use CrispyTheme\Content\MarkdownRenderer;

// ---------------------------------------------------------------------------
// Minimal WP_CLI stubs — defined once per process with class_exists guards
// so re-runs don't fatal on redeclaration.
// ---------------------------------------------------------------------------

if (!class_exists('WP_CLI_Command')) {
    class WP_CLI_Command {}
}

if (!class_exists('WP_CLI')) {
    class WP_CLI
    {
        /** @var array<string, mixed> */
        public static array $calls = [];

        /**
         * Reset recorded calls between tests.
         */
        public static function reset(): void
        {
            static::$calls = [];
        }

        public static function success(string $message): void
        {
            static::$calls[] = ['method' => 'success', 'message' => $message];
        }

        public static function error(string $message, bool $exit = true): void
        {
            static::$calls[] = ['method' => 'error', 'message' => $message];
            throw new \RuntimeException('WP_CLI::error: ' . $message);
        }

        public static function log(string $message): void
        {
            static::$calls[] = ['method' => 'log', 'message' => $message];
        }

        public static function warning(string $message): void
        {
            static::$calls[] = ['method' => 'warning', 'message' => $message];
        }

        public static function confirm(string $question): void
        {
            static::$calls[] = ['method' => 'confirm', 'question' => $question];
        }

        /** @param mixed $value */
        public static function add_command(string $name, mixed $value): void
        {
            static::$calls[] = ['method' => 'add_command', 'name' => $name];
        }
    }
}

// Nested namespace stub for WP_CLI\Utils
if (!class_exists('WP_CLI\Utils')) {
    // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace -- Intentional stub.
    class WP_CLI_Utils_Stub
    {
        /** @param mixed $default */
        public static function get_flag_value(array $assoc_args, string $flag, mixed $default = false): mixed
        {
            return $assoc_args[$flag] ?? $default;
        }
    }
}

// Map the function call `WP_CLI\Utils\get_flag_value()` used by ImportCommand.
// Brain Monkey lets us stub namespaced functions; we do it inside tests instead.

// ---------------------------------------------------------------------------
// Custom PHP stream wrapper: file_exists() and is_readable() both return true
// (via url_stat), but stream_open() returns false so file_get_contents() returns
// false — used to exercise ImportCommand line 97 without filesystem tricks.
// ---------------------------------------------------------------------------

if (!class_exists('CrispyFailStream')) {
    /**
     * Stream wrapper that always fails to open, making file_get_contents() return false
     * while file_exists() and is_readable() still return true.
     */
    class CrispyFailStream
    {
        /** @var resource|null */
        public $context;

        public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
        {
            return false;
        }

        /** @return array<string, int> */
        public function url_stat(string $path, int $flags): array
        {
            // Return a stat array that makes file_exists() and is_readable() return true.
            return [
                'dev'     => 0,
                'ino'     => 0,
                'mode'    => 0100644,
                'nlink'   => 1,
                'uid'     => 0,
                'gid'     => 0,
                'rdev'    => 0,
                'size'    => 42,
                'atime'   => 0,
                'mtime'   => 0,
                'ctime'   => 0,
                'blksize' => -1,
                'blocks'  => -1,
            ];
        }
    }
}

// ---------------------------------------------------------------------------

use CrispyTheme\CLI\ImportCommand;

describe('ImportCommand', function () {

    beforeEach(function () {
        WP_CLI::reset();

        // Stub the WP_CLI\Utils\get_flag_value function used throughout ImportCommand.
        Functions\when('WP_CLI\Utils\get_flag_value')->alias(
            static fn(array $assoc_args, string $flag, mixed $default = false): mixed =>
                $assoc_args[$flag] ?? $default
        );
    });

    // -----------------------------------------------------------------------
    // import()
    // -----------------------------------------------------------------------

    describe('import()', function () {

        it('errors when the post does not exist', function () {
            Functions\when('get_post')->justReturn(null);
            Functions\when('get_post_meta')->justReturn('');

            $cmd = new ImportCommand();

            expect(fn() => $cmd->import(['99', '/nonexistent.md'], []))
                ->toThrow(\RuntimeException::class, 'Post 99 not found.');
        });

        it('errors when the file does not exist', function () {
            Functions\when('get_post')->justReturn((object) ['ID' => 1]);

            $cmd = new ImportCommand();

            expect(fn() => $cmd->import(['1', '/no/such/file.md'], []))
                ->toThrow(\RuntimeException::class, 'File not found');
        });

        it('errors when the file is not readable', function () {
            Functions\when('get_post')->justReturn((object) ['ID' => 1]);
            Functions\when('get_post_meta')->justReturn('');

            // Create a real file, make it unreadable, then test.
            $file = tempnam(sys_get_temp_dir(), 'crispy_cli_');
            file_put_contents($file, 'content');
            chmod($file, 0000);

            try {
                $cmd = new ImportCommand();
                $cmd->import(['1', $file], []);
            } catch (\RuntimeException $e) {
                expect($e->getMessage())->toContain('not readable');
            } finally {
                chmod($file, 0644);
                unlink($file);
            }
        });

        it('asks for confirmation when post has existing content and no --force', function () {
            Functions\when('get_post')->justReturn((object) ['ID' => 1]);
            Functions\when('update_post_meta')->justReturn(true);

            $file = $this->createTempFile('# Hello');
            $this->postMeta[1][MarkdownRenderer::META_KEY] = 'old content';
            $this->mockPostMetaFunctions();

            $cmd = new ImportCommand();
            $cmd->import(['1', $file], []);

            $methods = array_column(WP_CLI::$calls, 'method');
            expect($methods)->toContain('confirm');
        });

        it('skips confirmation with --force when existing content is present', function () {
            Functions\when('get_post')->justReturn((object) ['ID' => 1]);
            Functions\when('update_post_meta')->justReturn(true);

            $file = $this->createTempFile('# Hello world');
            $this->postMeta[1][MarkdownRenderer::META_KEY] = 'old content';
            $this->mockPostMetaFunctions();

            $cmd = new ImportCommand();
            $cmd->import(['1', $file], ['force' => true]);

            $methods = array_column(WP_CLI::$calls, 'method');
            expect($methods)->not->toContain('confirm');
            expect($methods)->toContain('success');
        });

        it('imports markdown and reports success with word/char counts', function () {
            Functions\when('get_post')->justReturn((object) ['ID' => 1]);
            Functions\when('get_post_meta')->justReturn('');
            Functions\when('update_post_meta')->justReturn(true);

            $file = $this->createTempFile('# Hello world');

            $cmd = new ImportCommand();
            $cmd->import(['1', $file], []);

            $successCalls = array_filter(
                WP_CLI::$calls,
                static fn(array $c): bool => $c['method'] === 'success'
            );
            expect($successCalls)->not->toBeEmpty();
            $msg = array_values($successCalls)[0]['message'];
            expect($msg)->toContain('post 1');
            expect($msg)->toContain('words');
            expect($msg)->toContain('characters');
        });

        it('errors when update_post_meta returns false', function () {
            Functions\when('get_post')->justReturn((object) ['ID' => 1]);
            Functions\when('get_post_meta')->justReturn('');
            Functions\when('update_post_meta')->justReturn(false);

            $file = $this->createTempFile('# Hello');

            $cmd = new ImportCommand();

            expect(fn() => $cmd->import(['1', $file], []))
                ->toThrow(\RuntimeException::class, 'Failed to update post meta.');
        });

        it('errors when file_get_contents returns false (line 97 — stream-open failure)', function () {
            // Register a custom stream wrapper whose stream_open() returns false,
            // making file_get_contents() return false even though file_exists()
            // and is_readable() both return true via url_stat().
            if (!in_array('crispyfail', stream_get_wrappers(), true)) {
                stream_wrapper_register('crispyfail', \CrispyFailStream::class);
            }

            Functions\when('get_post')->justReturn((object) ['ID' => 1]);
            Functions\when('get_post_meta')->justReturn('');

            $caught = null;
            try {
                $cmd = new ImportCommand();
                // Suppress the PHP notice that stream_open failure may emit.
                @$cmd->import(['1', 'crispyfail://testfile.md'], []);
            } catch (\RuntimeException $e) {
                $caught = $e->getMessage();
            }

            expect($caught)->not->toBeNull();
            expect($caught)->toContain('Failed to read file');
        });
    });

    // -----------------------------------------------------------------------
    // export()
    // -----------------------------------------------------------------------

    describe('export()', function () {

        it('errors when the post does not exist', function () {
            Functions\when('get_post')->justReturn(null);
            Functions\when('get_post_meta')->justReturn('');

            $cmd = new ImportCommand();

            expect(fn() => $cmd->export(['99', '/tmp/out.md'], []))
                ->toThrow(\RuntimeException::class, 'Post 99 not found.');
        });

        it('errors when post has no markdown content', function () {
            Functions\when('get_post')->justReturn((object) ['ID' => 1]);
            Functions\when('get_post_meta')->justReturn('');

            $cmd = new ImportCommand();

            expect(fn() => $cmd->export(['1', '/tmp/out.md'], []))
                ->toThrow(\RuntimeException::class, 'has no markdown content');
        });

        it('exports markdown to file successfully', function () {
            Functions\when('get_post')->justReturn((object) ['ID' => 1]);

            $this->postMeta[1][MarkdownRenderer::META_KEY] = '# My Post';
            $this->mockPostMetaFunctions();

            $outFile = tempnam(sys_get_temp_dir(), 'crispy_export_') . '.md';
            $this->tempFiles[] = $outFile;

            $cmd = new ImportCommand();
            $cmd->export(['1', $outFile], []);

            expect(file_exists($outFile))->toBeTrue();
            expect(file_get_contents($outFile))->toBe('# My Post');

            $methods = array_column(WP_CLI::$calls, 'method');
            expect($methods)->toContain('success');
        });

        it('asks for confirmation when output file exists and no --force', function () {
            Functions\when('get_post')->justReturn((object) ['ID' => 1]);

            $this->postMeta[1][MarkdownRenderer::META_KEY] = '# My Post';
            $this->mockPostMetaFunctions();

            $outFile = $this->createTempFile('existing content');

            $cmd = new ImportCommand();
            $cmd->export(['1', $outFile], []);

            $methods = array_column(WP_CLI::$calls, 'method');
            expect($methods)->toContain('confirm');
        });

        it('skips confirmation with --force when file already exists', function () {
            Functions\when('get_post')->justReturn((object) ['ID' => 1]);

            $this->postMeta[1][MarkdownRenderer::META_KEY] = '# Overwrite me';
            $this->mockPostMetaFunctions();

            $outFile = $this->createTempFile('existing content');

            $cmd = new ImportCommand();
            $cmd->export(['1', $outFile], ['force' => true]);

            $methods = array_column(WP_CLI::$calls, 'method');
            expect($methods)->not->toContain('confirm');
            expect($methods)->toContain('success');
        });

        it('errors when file_put_contents returns false (line 170 — write failure)', function () {
            Functions\when('get_post')->justReturn((object) ['ID' => 1]);

            $this->postMeta[1][MarkdownRenderer::META_KEY] = '# My Post';
            $this->mockPostMetaFunctions();

            // A path in a non-existent directory guarantees file_put_contents returns false.
            // file_exists() will return false (no confirm prompt) so we go straight to the write.
            $nonExistentPath = '/nonexistent_crispy_dir_' . uniqid() . '/output.md';

            $caught = null;
            try {
                $cmd = new ImportCommand();
                // Suppress the PHP warning that file_put_contents emits on failure.
                @$cmd->export(['1', $nonExistentPath], ['force' => true]);
            } catch (\RuntimeException $e) {
                $caught = $e->getMessage();
            }

            expect($caught)->not->toBeNull();
            expect($caught)->toContain('Failed to write file');
        });
    });

    // -----------------------------------------------------------------------
    // cache()
    // -----------------------------------------------------------------------

    describe('cache()', function () {

        beforeEach(function () {
            // Mock DB-level functions used by TransientCache.
            Functions\when('delete_transient')->justReturn(true);
            Functions\when('size_format')->alias(
                static fn(int $bytes, int $decimals = 0): string => $bytes . ' B'
            );
        });

        it('errors on unknown action', function () {
            $cmd = new ImportCommand();

            expect(fn() => $cmd->cache(['bogus'], []))
                ->toThrow(\RuntimeException::class, 'Unknown action');
        });

        it('errors when clear is called without post_id or --all', function () {
            $cmd = new ImportCommand();

            expect(fn() => $cmd->cache(['clear'], []))
                ->toThrow(\RuntimeException::class, 'Please specify a post ID');
        });

        it('clears all cache with --all flag', function () {
            global $wpdb;
            $wpdb = new class {
                public string $options  = 'wp_options';
                /** @param mixed ...$args */
                public function prepare(string $sql, mixed ...$args): string
                {
                    return $sql;
                }
                /** @return string[] */
                public function get_col(string $sql): array
                {
                    return ['_transient_crispy_md_1_abc', '_transient_crispy_md_2_def'];
                }
            };

            $cmd = new ImportCommand();
            $cmd->cache(['clear'], ['all' => true]);

            $methods = array_column(WP_CLI::$calls, 'method');
            expect($methods)->toContain('success');
            $msg = array_values(
                array_filter(WP_CLI::$calls, static fn(array $c): bool => $c['method'] === 'success')
            )[0]['message'];
            expect($msg)->toContain('Cleared');
        });

        it('clears cache for a specific post', function () {
            global $wpdb;
            $wpdb = new class {
                public string $options  = 'wp_options';
                /** @param mixed ...$args */
                public function prepare(string $sql, mixed ...$args): string
                {
                    return $sql;
                }
                /** @return string[] */
                public function get_col(string $sql): array
                {
                    return ['_transient_crispy_md_5_abc'];
                }
            };

            $cmd = new ImportCommand();
            $cmd->cache(['clear', '5'], []);

            $methods = array_column(WP_CLI::$calls, 'method');
            expect($methods)->toContain('success');
            $msg = array_values(
                array_filter(WP_CLI::$calls, static fn(array $c): bool => $c['method'] === 'success')
            )[0]['message'];
            expect($msg)->toContain('post 5');
        });

        it('logs stats action output', function () {
            global $wpdb;
            $wpdb = new class {
                public string $options = 'wp_options';
                /** @param mixed ...$args */
                public function prepare(string $sql, mixed ...$args): string
                {
                    return $sql;
                }
                /** @return object[] */
                public function get_results(string $sql): array
                {
                    return [
                        (object) ['option_name' => '_transient_crispy_md_1_abc', 'size' => '512'],
                        (object) ['option_name' => '_transient_crispy_md_2_def', 'size' => '256'],
                    ];
                }
            };

            $cmd = new ImportCommand();
            $cmd->cache(['stats'], []);

            $logMessages = array_map(
                static fn(array $c): string => $c['message'] ?? '',
                array_filter(WP_CLI::$calls, static fn(array $c): bool => $c['method'] === 'log')
            );
            $allLogs = implode(' ', $logMessages);
            expect($allLogs)->toContain('Cached entries');
            expect($allLogs)->toContain('Total size');
        });
    });

    // -----------------------------------------------------------------------
    // bulk_import()
    // -----------------------------------------------------------------------

    describe('bulk_import()', function () {

        it('errors when directory does not exist', function () {
            Functions\when('get_current_user_id')->justReturn(1);

            $cmd = new ImportCommand();

            expect(fn() => $cmd->bulk_import(['/no/such/dir'], []))
                ->toThrow(\RuntimeException::class, 'Directory not found');
        });

        it('warns when no .md files are found in directory', function () {
            Functions\when('get_current_user_id')->justReturn(1);

            $dir = sys_get_temp_dir() . '/crispy_bulk_empty_' . uniqid();
            mkdir($dir);

            try {
                $cmd = new ImportCommand();
                $cmd->bulk_import([$dir], []);
            } finally {
                rmdir($dir);
            }

            $methods = array_column(WP_CLI::$calls, 'method');
            expect($methods)->toContain('warning');
        });

        it('logs dry-run output without creating posts', function () {
            Functions\when('get_current_user_id')->justReturn(1);

            $dir = sys_get_temp_dir() . '/crispy_bulk_dry_' . uniqid();
            mkdir($dir);
            file_put_contents($dir . '/hello-world.md', '# Hello World');

            try {
                $cmd = new ImportCommand();
                $cmd->bulk_import([$dir], ['dry-run' => true]);
            } finally {
                unlink($dir . '/hello-world.md');
                rmdir($dir);
            }

            $logMessages = array_map(
                static fn(array $c): string => $c['message'] ?? '',
                array_filter(WP_CLI::$calls, static fn(array $c): bool => $c['method'] === 'log')
            );
            $allLogs = implode(' ', $logMessages);
            expect($allLogs)->toContain('Dry run');
            expect($allLogs)->toContain('hello-world.md');
        });

        it('bulk-imports .md files and reports success', function () {
            Functions\when('get_current_user_id')->justReturn(1);
            Functions\when('wp_insert_post')->justReturn(42);
            Functions\when('update_post_meta')->justReturn(true);
            Functions\when('is_wp_error')->justReturn(false);

            $dir = sys_get_temp_dir() . '/crispy_bulk_real_' . uniqid();
            mkdir($dir);
            file_put_contents($dir . '/first-post.md', '# First Post');
            file_put_contents($dir . '/second-post.md', '# Second Post');

            try {
                $cmd = new ImportCommand();
                $cmd->bulk_import([$dir], []);
            } finally {
                unlink($dir . '/first-post.md');
                unlink($dir . '/second-post.md');
                rmdir($dir);
            }

            $successCalls = array_filter(
                WP_CLI::$calls,
                static fn(array $c): bool => $c['method'] === 'success'
            );
            expect($successCalls)->not->toBeEmpty();
            $msg = array_values($successCalls)[0]['message'];
            expect($msg)->toContain('Imported');
        });

        it('warns (not fatals) when wp_insert_post returns WP_Error', function () {
            Functions\when('get_current_user_id')->justReturn(1);
            Functions\when('is_wp_error')->justReturn(true);

            $errorObj = new class {
                public function get_error_message(): string
                {
                    return 'Insert failed';
                }
            };
            Functions\when('wp_insert_post')->justReturn($errorObj);

            $dir = sys_get_temp_dir() . '/crispy_bulk_err_' . uniqid();
            mkdir($dir);
            file_put_contents($dir . '/broken.md', '# Broken');

            try {
                $cmd = new ImportCommand();
                $cmd->bulk_import([$dir], []);
            } finally {
                unlink($dir . '/broken.md');
                rmdir($dir);
            }

            $methods = array_column(WP_CLI::$calls, 'method');
            expect($methods)->toContain('warning');
            // Success with 0 imports still fires.
            $successCalls = array_filter(
                WP_CLI::$calls,
                static fn(array $c): bool => $c['method'] === 'success'
            );
            expect($successCalls)->not->toBeEmpty();
        });

        it('passes custom --post-type and --status to wp_insert_post', function () {
            Functions\when('get_current_user_id')->justReturn(1);
            Functions\when('is_wp_error')->justReturn(false);
            Functions\when('update_post_meta')->justReturn(true);

            $captured = [];
            Functions\when('wp_insert_post')->alias(
                static function (array $args) use (&$captured): int {
                    $captured = $args;
                    return 10;
                }
            );

            $dir = sys_get_temp_dir() . '/crispy_bulk_type_' . uniqid();
            mkdir($dir);
            file_put_contents($dir . '/a-page.md', '# A Page');

            try {
                $cmd = new ImportCommand();
                $cmd->bulk_import([$dir], ['post-type' => 'page', 'status' => 'publish', 'author' => '7']);
            } finally {
                unlink($dir . '/a-page.md');
                rmdir($dir);
            }

            expect($captured['post_type'])->toBe('page');
            expect($captured['post_status'])->toBe('publish');
            expect($captured['post_author'])->toBe(7);
        });
    });
});
