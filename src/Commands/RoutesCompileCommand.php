<?php

namespace WebImage\Commands;

use WebImage\Application\ApplicationInterface;
use WebImage\Config\Config;
use WebImage\Console\ConsoleInput;
use WebImage\Console\ConsoleOutput;
use WebImage\Console\FlagOption;
use WebImage\Core\Dictionary;
use WebImage\Files\FileWatcher;
use WebImage\Route\YamlRouteCompiler;

class RoutesCompileCommand extends Command implements ConfigurableCommandInterface
{
    private ?ApplicationInterface $app = null;

    protected function configure(): void
    {
        $this->setName('routes:compile')
            ->setDescription('Compile YAML route definitions to PHP')
            ->setGroup('routing')
            ->addOption(FlagOption::create('watch', 'Watch for changes and recompile automatically', 'w'))
            ->addOption(FlagOption::create('report', 'Show compilation status without compiling', 'r'))
            ->addOption(FlagOption::create('force', 'Force recompilation even if files are up to date', 'f'))
            ->addOption(FlagOption::create('verbose', 'Show detailed output', 'v'));
    }

    public function configureCommand(): void
    {
        // Get app from container if available
        if ($this->getContainer()->has(ApplicationInterface::class)) {
            $this->app = $this->getContainer()->get(ApplicationInterface::class);
        }
    }

    public function execute(ConsoleInput $input, ConsoleOutput $output): int
    {
        $isReport = $input->hasOption('report');
        $isWatch = $input->hasOption('watch');
        $isForce = $input->hasOption('force');
        $isVerbose = $input->hasOption('verbose');

        try {
            $config = $this->getRouterConfig();
            $sourceFiles = $this->getSourceFiles($config);
            $compiledFile = $this->getCompiledFile($config);
            $compiler = new YamlRouteCompiler();

            if ($isReport) {
                return $this->showReport($compiler, $compiledFile, $sourceFiles, $output, $isVerbose);
            }

            if ($isWatch) {
                return $this->watchMode($compiler, $sourceFiles, $compiledFile, $output, $isVerbose);
            }

            return $this->compileRoutes($compiler, $sourceFiles, $compiledFile, $output, $isForce, $isVerbose);

        } catch (\Exception $e) {
            $output->error('Error: ' . $e->getMessage());
            if ($isVerbose) {
                $output->writeln('');
                $output->writeln('<red>Stack trace:</red>');
                $output->writeln($e->getTraceAsString());
            }
            return 1;
        }
    }

    /**
     * Compile routes
     */
    private function compileRoutes(
        YamlRouteCompiler $compiler,
        array $sourceFiles,
        string $compiledFile,
        ConsoleOutput $output,
        bool $force,
        bool $verbose
    ): int {
        // Check if recompilation is needed
        if (!$force && !$compiler->needsRecompilation($compiledFile, $sourceFiles)) {
            $output->success('Routes are already up to date.');
            $output->writeln('Use --force to recompile anyway.');
            return 0;
        }

        if ($verbose) {
            $output->info('Compiling routes...');
            $output->writeln('');
            $output->writeln('Source files:');
            foreach ($sourceFiles as $file) {
                $output->writeln("  - {$file}");
            }
            $output->writeln('');
        }

        try {
            // Compile routes
            $routes = $compiler->compile($sourceFiles);

            // Write to file
            $compiler->writeToFile($routes, $compiledFile);

            $totalRoutes = count($routes) - 1; // Exclude _meta

            $output->success("Routes compiled successfully!");
            $output->writeln("  Total routes: <cyan>{$totalRoutes}</cyan>");
            $output->writeln("  Output: <cyan>{$compiledFile}</cyan>");

            if ($verbose) {
                $output->writeln('');
                $this->showRouteBreakdown($routes, $output);
            }

            return 0;

        } catch (\Exception $e) {
            $output->error('Compilation failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Watch mode - recompile on file changes
     */
    private function watchMode(
        YamlRouteCompiler $compiler,
        array $sourceFiles,
        string $compiledFile,
        ConsoleOutput $output,
        bool $verbose
    ): int {
        $output->info('Starting watch mode...');
        $output->writeln('Press Ctrl+C to stop');
        $output->writeln('');

        // Initial compilation - this discovers all files including includes
        $output->info('Initial compilation...');

        try {
            $routes = $compiler->compile($sourceFiles);
            $compiler->writeToFile($routes, $compiledFile);

            $totalRoutes = count($routes) - 1;
            $allFiles = $compiler->getDiscoveredSourceFiles();

            $output->success("Routes compiled successfully! ({$totalRoutes} routes)");
            $output->writeln('');

            if ($verbose) {
                $output->writeln('Watching files:');
                foreach ($allFiles as $file) {
                    $output->writeln("  - {$file}");
                }
                $output->writeln('');
            }

            $output->success('Watching for changes...');
            $output->writeln('');

        } catch (\Exception $e) {
            $output->error('Initial compilation failed. Fix errors before watching.');
            if ($verbose) {
                $output->writeln($e->getTraceAsString());
            }
            return 1;
        }

        // Setup file watcher with initially discovered files
        $watcher = new FileWatcher();
        $watcher->addFiles($allFiles);

        $watcher->watch(
            function(array $changed) use ($compiler, $sourceFiles, $compiledFile, $output, $verbose, $watcher) {
                $output->writeln('');
                $output->info('[' . date('H:i:s') . '] Change detected in:');
                foreach ($changed as $file) {
                    $output->writeln("  - {$file}");
                }
                $output->writeln('');

                try {
                    // Recompile
                    $routes = $compiler->compile($sourceFiles);
                    $compiler->writeToFile($routes, $compiledFile);

                    // Get updated list of files (includes may have changed)
                    $newFiles = $compiler->getDiscoveredSourceFiles();

                    // Update watcher with new file list
                    $watcher->clear();
                    $watcher->addFiles($newFiles);

                    $totalRoutes = count($routes) - 1;
                    $output->success('[' . date('H:i:s') . '] Routes recompiled successfully! (' . $totalRoutes . ' routes)');

                    if ($verbose) {
                        $output->writeln('');
                        $output->writeln('Updated watch list:');
                        foreach ($newFiles as $file) {
                            $output->writeln("  - {$file}");
                        }
                    }

                } catch (\Exception $e) {
                    $output->error('[' . date('H:i:s') . '] Compilation failed: ' . $e->getMessage());
                    if ($verbose) {
                        $output->writeln($e->getTraceAsString());
                    }
                }

                $output->writeln('');
            },
            1 // Check every second
        );

        return 0;
    }

    /**
     * Show compilation report
     */
    private function showReport(
        YamlRouteCompiler $compiler,
        string $compiledFile,
        array $sourceFiles,
        ConsoleOutput $output,
        bool $verbose
    ): int {
        $output->writeln('<yellow>Route Compilation Status</yellow>');
        $output->writeln(str_repeat('=', 60));
        $output->writeln('');

        $report = $compiler->getReport($compiledFile, $sourceFiles);

        // Compiled file status
        $output->writeln('<yellow>Compiled File:</yellow> ' . $compiledFile);

        if ($report['exists']) {
            $status = $report['needs_recompilation'] ? '<yellow>OUTDATED (needs recompilation)</yellow>' : '<green>UP TO DATE</green>';
            $output->writeln('  Status: ' . $status);

            if ($report['generated_at']) {
                $time = date('Y-m-d H:i:s', $report['generated_at']);
                $ago = $this->timeAgo($report['generated_at']);
                $output->writeln('  Generated: ' . $time . ' (' . $ago . ')');
            }

            $output->writeln('  Total Routes: <cyan>' . $report['total_routes'] . '</cyan>');
        } else {
            $output->writeln('  Status: <red>NOT COMPILED</red>');
        }

        $output->writeln('');

        // Source files
        $output->writeln('<yellow>Source Files:</yellow>');

        foreach ($report['source_files'] as $fileNode) {
            $this->printFileNode($fileNode, $output, $verbose);
        }

        $output->writeln('');

        // Summary
        $totalFiles = count($report['outdated_files']) + count($report['source_files']);
        $outdatedCount = count($report['outdated_files']);

        $output->writeln('<yellow>Summary:</yellow>');
        $output->writeln('  - ' . $totalFiles . ' source file(s)');

        if ($outdatedCount > 0) {
            $output->writeln('  - <yellow>' . $outdatedCount . ' file(s) need recompilation</yellow>');
            $output->writeln('');
            $output->writeln('Run: <cyan>php console routes:compile</cyan>');
        } else if ($report['needs_recompilation']) {
            $output->writeln('  - <yellow>Compiled file needs recompilation</yellow>');
            $output->writeln('');
            $output->writeln('Run: <cyan>php console routes:compile</cyan>');
        } else if ($report['exists']) {
            $output->writeln('  - <green>Compiled file is current</green>');
        } else {
            $output->writeln('  - <yellow>Routes not yet compiled</yellow>');
            $output->writeln('');
            $output->writeln('Run: <cyan>php console routes:compile</cyan>');
        }

        return 0;
    }

    /**
     * Print file node (recursive for tree structure)
     */
    private function printFileNode(array $node, ConsoleOutput $output, bool $verbose, bool $isLast = true): void
    {
        $indent = str_repeat('  ', $node['level']);
        $prefix = $node['level'] > 0 ? ($isLast ? '└─ ' : '├─ ') : '';

        $status = '✓';
        $color = 'green';

        if (!$node['exists']) {
            $status = '✗';
            $color = 'red';
        } elseif ($node['outdated']) {
            $status = '!';
            $color = 'yellow';
        }

        $file = $node['file'];
        $output->write("{$indent}{$prefix}<{$color}>{$status}</{$color}> {$file}");

        if ($verbose && $node['exists']) {
            $output->writeln('');
            $mtime = date('Y-m-d H:i:s', $node['modified']);
            $output->writeln("{$indent}    Modified: {$mtime}");

            if ($node['outdated']) {
                $output->writeln("{$indent}    <yellow>Status: NEWER than compiled</yellow>");
            } else {
                $output->writeln("{$indent}    Status: Compiled");
            }
        } else {
            $output->writeln('');
        }

        // Print children
        $childCount = count($node['children']);
        foreach ($node['children'] as $i => $child) {
            $isLastChild = ($i === $childCount - 1);
            $this->printFileNode($child, $output, $verbose, $isLastChild);
        }
    }

    /**
     * Show route breakdown by source file
     */
    private function showRouteBreakdown(array $routes, ConsoleOutput $output): void
    {
        $output->writeln('<yellow>Routes by source file:</yellow>');

        $byFile = [];
        foreach ($routes as $route) {
            if ($route === $routes['_meta']) continue;

            $file = $route['_source_comment'] ?? 'unknown';
            if (!isset($byFile[$file])) {
                $byFile[$file] = 0;
            }
            $byFile[$file]++;
        }

        foreach ($byFile as $file => $count) {
            $output->writeln("  {$file}: <cyan>{$count}</cyan> routes");
        }
    }

    /**
     * Get router configuration
     */
    private function getRouterConfig(): array
    {
        if (!$this->app) {
            throw new \RuntimeException('Application is unavailable. Cannot determine route files.');
        }

        $config = $this->app->getConfig();
        $routerConfig = $config['router'] ?? [];

        return $routerConfig instanceof Dictionary ? $routerConfig->toArray() : $routerConfig;
    }

    /**
     * Get source YAML files to compile
     */
    private function getSourceFiles(array $config): array
    {
        if (isset($config['routeFiles']) && is_array($config['routeFiles'])) {
            return $config['routeFiles'];
        }

        // Default to /app/config/routes.yaml
        $defaultFile = $this->getDefaultRoutesFile();

        if (!file_exists($defaultFile)) {
            throw new \RuntimeException(
                "Default routes file not found: {$defaultFile}\n" .
                "Create the file or configure 'router.routeFiles' in your config."
            );
        }

        return [$defaultFile];
    }

    /**
     * Get compiled output file path
     */
    private function getCompiledFile(array $config): string
    {
        if (isset($config['compiledFile'])) {
            return $config['compiledFile'];
        }

        // Default to routes.yaml.php in config directory
        $defaultFile = $this->getDefaultRoutesFile();
        return dirname($defaultFile) . '/routes.yaml.php';
    }

    /**
     * Get default routes file path
     */
    private function getDefaultRoutesFile(): string
    {
        if ($this->app) {
            return $this->app->getProjectPath() . '/config/routes.yaml';
        }

        // Fallback: assume we're in project root when running console
        return getcwd() . '/app/config/routes.yaml';
    }

    /**
     * Format time ago
     */
    private function timeAgo(int $timestamp): string
    {
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return $diff . ' second(s) ago';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' minute(s) ago';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' hour(s) ago';
        } else {
            return floor($diff / 86400) . ' day(s) ago';
        }
    }
}