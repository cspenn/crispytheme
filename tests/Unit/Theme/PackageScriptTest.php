<?php
/**
 * Release packaging tests (bin/package.sh and .github/workflows/release.yml).
 *
 * The plan (`bin/package.sh --plan`) must hold every folder that functions.php, the templates, and the
 * patterns need, and none of the folders that stay out. A real run with a stub build proves the zip list.
 *
 * @package CrispyTheme\Tests\Unit\Theme
 */

declare(strict_types=1);

/**
 * Read the plan of the packaging script as name => list of words.
 *
 * @return array<string, array<int, string>>
 */
function crispyPackagePlan(string $root): array
{
    $output = shell_exec('bash ' . escapeshellarg($root . '/bin/package.sh') . ' --plan 2>&1');
    $plan   = [];
    foreach (explode("\n", trim((string) $output)) as $line) {
        [$key, $value] = array_pad(explode(': ', $line, 2), 2, '');
        $plan[$key]    = array_values(array_filter(explode(' ', $value), static fn(string $w): bool => '' !== $w));
    }

    return $plan;
}

beforeEach(function () {
    $this->root = dirname(__DIR__, 3);
    $this->plan = crispyPackagePlan($this->root);
});

describe('release plan', function () {
    it('holds every folder that the code reads at run time', function () {
        $shipped = array_merge($this->plan['INCLUDE_DIRS'], $this->plan['OPTIONAL_DIRS'], $this->plan['GENERATED']);

        // Every CRISPY_THEME_DIR path in functions.php, src, and inc names a folder or file that ships.
        $sources = array_merge([$this->root . '/functions.php'], glob($this->root . '/src/*/*.php') ?: [], glob($this->root . '/inc/*.php') ?: []);
        $needed  = [];
        foreach ($sources as $file) {
            if (preg_match_all("#CRISPY_THEME_DIR\\s*\\.\\s*'/([A-Za-z0-9_-]+)#", (string) file_get_contents($file), $m)) {
                $needed = array_merge($needed, $m[1]);
            }
        }
        expect($needed)->not->toBeEmpty();
        foreach (array_unique($needed) as $folder) {
            expect($shipped)->toContain($folder);
        }
    });

    it('holds the folders for templates, template parts, patterns, source, helpers, and assets', function () {
        foreach (['templates', 'parts', 'block-patterns', 'src', 'inc', 'assets'] as $folder) {
            expect($this->plan['INCLUDE_DIRS'])->toContain($folder);
            expect(is_dir($this->root . '/' . $folder))->toBeTrue();
        }
        expect($this->plan['GENERATED'])->toContain('vendor', 'build');
        expect($this->plan['INCLUDE_FILES'])->toContain('functions.php', 'style.css', 'theme.json', 'index.php');
    });

    it('holds a pattern file and a template part for every pattern and part a template names', function () {
        foreach (glob($this->root . '/templates/*.html') ?: [] as $template) {
            $html = (string) file_get_contents($template);
            if (preg_match_all('#wp:template-part \{"slug":"([a-z-]+)"#', $html, $m)) {
                foreach ($m[1] as $slug) {
                    expect(file_exists($this->root . '/parts/' . $slug . '.html'))->toBeTrue("$slug in " . basename($template));
                }
            }
            if (preg_match_all('#wp:pattern \{"slug":"crispytheme/([a-z-]+)"#', $html, $m)) {
                foreach ($m[1] as $slug) {
                    expect(file_exists($this->root . '/block-patterns/' . $slug . '.php'))->toBeTrue("$slug in " . basename($template));
                }
            }
        }
    });

    it('leaves out tests, notes, inputs, outputs, tool state, and development configuration', function () {
        $excluded = $this->plan['EXCLUDE_NAMES'];
        foreach (['tests', 'input', 'output', 'graphify-out', 'docs', 'node_modules', '.git', 'archived', 'logs', 'references', 'temp', 'package.json', 'composer.json', 'phpstan.neon'] as $name) {
            expect($excluded)->toContain($name);
        }
        $shipped = array_merge($this->plan['INCLUDE_DIRS'], $this->plan['OPTIONAL_DIRS'], $this->plan['INCLUDE_FILES']);
        expect(array_intersect($shipped, $excluded))->toBe([]);
        expect($this->plan['ASSET_EXCLUDES'])->toContain('assets/js/src');
    });

    it('requires the booking page files and the built assets', function () {
        foreach (['src/Booking/BookingPage.php', 'block-patterns/booking-page.php', 'templates/page-public-speaking.html', 'build/dark-mode-toggle.js', 'vendor/autoload.php'] as $path) {
            expect($this->plan['REQUIRED_PATHS'])->toContain($path);
        }
    });
});

describe('release workflow', function () {
    it('calls the same script and copies no folder that does not exist', function () {
        $yaml = (string) file_get_contents($this->root . '/.github/workflows/release.yml');

        expect($yaml)->toContain('bin/package.sh');
        expect($yaml)->not->toContain('cp -r patterns');
        expect($yaml)->not->toContain('cp -r includes');
        expect($yaml)->not->toContain('npm ci');
    });

    it('refuses an output folder under /tmp', function () {
        $output = shell_exec('bash ' . escapeshellarg($this->root . '/bin/package.sh') . ' --out /tmp/crispy-release-test --prebuilt /nonexistent 2>&1');

        expect((string) $output)->toContain('must not be under /tmp');
    });
});

describe('release zip', function () {
    it('builds a zip with the real folders and none of the excluded ones', function () {
        $out   = $this->root . '/temp/release-test';
        $stub  = $out . '/stub-build';
        shell_exec('rm -rf ' . escapeshellarg($out));
        mkdir($stub, 0777, true);
        file_put_contents($stub . '/dark-mode-toggle.js', '// stub build for the packaging test');

        $before = (string) shell_exec('git -C ' . escapeshellarg($this->root) . ' status --porcelain');
        $output = (string) shell_exec(
            'bash ' . escapeshellarg($this->root . '/bin/package.sh') . ' --out ' . escapeshellarg($out)
            . ' --version 0.0.0-test --prebuilt ' . escapeshellarg($stub) . ' 2>&1'
        );
        $after = (string) shell_exec('git -C ' . escapeshellarg($this->root) . ' status --porcelain');

        expect($output)->toContain('crispytheme-v2-0.0.0-test.zip');
        expect($after)->toBe($before);

        $files = array_filter(explode("\n", (string) file_get_contents($out . '/crispytheme-v2-0.0.0-test.files.txt')));
        $has   = static fn(string $path): bool => in_array('crispytheme-v2/' . $path, $files, true);
        foreach (['functions.php', 'style.css', 'theme.json', 'inc/template-tags.php', 'src/Booking/BookingPage.php', 'block-patterns/booking-page.php', 'templates/page-public-speaking.html', 'parts/header.html', 'assets/css/booking-page.css', 'assets/images/booking/manifest.json', 'vendor/autoload.php', 'build/dark-mode-toggle.js'] as $path) {
            expect($has($path))->toBeTrue($path);
        }
        foreach (['tests/', 'input/', 'output/', 'graphify-out/', 'docs/', 'node_modules/', '.git/', 'archived/', 'logs/', 'references/', 'temp/', 'assets/js/src/', 'composer.json', 'package.json', 'phpstan.neon'] as $name) {
            foreach ($files as $file) {
                expect(str_contains($file, 'crispytheme-v2/' . $name))->toBeFalse($file);
            }
        }

        shell_exec('rm -rf ' . escapeshellarg($out));
    });
});
