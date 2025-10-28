<?php

namespace WebImage\Route;

use Symfony\Component\Yaml\Yaml;

class YamlRouteCompiler
{
    private array $sourceFiles = [];
    private array $dependencies = [];
    private array $compiledRoutes = [];

    /**
     * Compile YAML route files into a PHP array
     *
     * @param array $yamlFiles Array of YAML file paths to compile
     * @return array Complete route array with metadata
     */
    public function compile(array $yamlFiles): array
    {
        $this->sourceFiles = [];
        $this->dependencies = [];
        $this->compiledRoutes = [];

        foreach ($yamlFiles as $file) {
            if (!file_exists($file)) {
                throw new \RuntimeException("Route file not found: {$file}");
            }
            $this->compileFile($file, null);
        }

        return $this->buildFinalArray();
    }

    /**
     * Get all discovered source files (including includes) from the last compilation
     *
     * @return array Array of file paths that were compiled
     */
    public function getDiscoveredSourceFiles(): array
    {
        return array_keys($this->sourceFiles);
    }

    /**
     * Check if recompilation is needed
     *
     * @param string $compiledFile Path to compiled PHP file
     * @param array $sourceFiles Array of source YAML files
     * @return bool True if recompilation needed
     */
    public function needsRecompilation(string $compiledFile, array $sourceFiles): bool
    {
        if (!file_exists($compiledFile)) {
            return true;
        }

        // Load metadata from compiled file
        $compiled = require $compiledFile;
        if (!isset($compiled['_meta']['source_files'])) {
            return true;
        }

        $compiledMeta = $compiled['_meta']['source_files'];

        // Check if any source file has been modified
        $allFiles = $this->getAllSourceFiles($sourceFiles);

        foreach ($allFiles as $file) {
            if (!isset($compiledMeta[$file])) {
                return true; // New file added
            }

            if (filemtime($file) > $compiledMeta[$file]) {
                return true; // File modified since compilation
            }
        }

        // Check if any compiled files no longer exist
        foreach (array_keys($compiledMeta) as $file) {
            if (!file_exists($file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get compilation report
     *
     * @param string $compiledFile Path to compiled PHP file
     * @param array $sourceFiles Array of source YAML files
     * @return array Report data
     */
    public function getReport(string $compiledFile, array $sourceFiles): array
    {
        $report = [
            'compiled_file' => $compiledFile,
            'exists' => file_exists($compiledFile),
            'needs_recompilation' => false,
            'generated_at' => null,
            'total_routes' => 0,
            'source_files' => [],
            'outdated_files' => [],
        ];

        if ($report['exists']) {
            $compiled = require $compiledFile;
            $report['generated_at'] = $compiled['_meta']['generated_at'] ?? null;
            $report['total_routes'] = count($compiled) - 1; // Exclude _meta

            $compiledMeta = $compiled['_meta']['source_files'] ?? [];
            $dependencies = $compiled['_meta']['dependencies'] ?? [];

            // Build file tree
            foreach ($sourceFiles as $file) {
                $report['source_files'][] = $this->buildFileTree($file, $compiledMeta, $dependencies);
            }

            // Check for outdated files
            $report['needs_recompilation'] = $this->needsRecompilation($compiledFile, $sourceFiles);

            if ($report['needs_recompilation']) {
                $allFiles = $this->getAllSourceFiles($sourceFiles);
                foreach ($allFiles as $file) {
                    if (!isset($compiledMeta[$file]) || filemtime($file) > $compiledMeta[$file]) {
                        $report['outdated_files'][] = $file;
                    }
                }
            }
        } else {
            $report['needs_recompilation'] = true;
            foreach ($sourceFiles as $file) {
                $report['source_files'][] = $this->buildFileTree($file, [], []);
            }
        }

        return $report;
    }

    /**
     * Write compiled routes to PHP file
     *
     * @param array $routes Compiled routes array
     * @param string $outputFile Output file path
     */
    public function writeToFile(array $routes, string $outputFile): void
    {
        $dir = dirname($outputFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $phpCode = $this->generatePhpCode($routes);

        if (file_put_contents($outputFile, $phpCode) === false) {
            throw new \RuntimeException("Failed to write compiled routes to: {$outputFile}");
        }
    }

    /**
     * Compile a single YAML file and its includes
     */
    private function compileFile(string $file, ?string $parentFile): void
    {
        $file = realpath($file);

        if (isset($this->sourceFiles[$file])) {
            return; // Already processed
        }

        $this->sourceFiles[$file] = filemtime($file);

        if ($parentFile) {
            if (!isset($this->dependencies[$parentFile])) {
                $this->dependencies[$parentFile] = [];
            }
            $this->dependencies[$parentFile][] = $file;
        }

        $content = file_get_contents($file);
        $data = Yaml::parse($content);

        if (!is_array($data)) {
            throw new \RuntimeException("Invalid YAML in file: {$file}");
        }

        // Handle includes first
        if (isset($data['include'])) {
            $this->processIncludes($data['include'], $file);
            unset($data['include']);
        }

        // Process routes
        if (isset($data['routes'])) {
            $this->processRoutes($data['routes'], $file);
        }
    }

    /**
     * Process include directives
     */
    private function processIncludes(array $includes, string $currentFile): void
    {
        $currentDir = dirname($currentFile);

        foreach ($includes as $includePath) {
            $resolvedPath = $this->resolvePath($includePath, $currentDir);

            if (!file_exists($resolvedPath)) {
                throw new \RuntimeException("Included file not found: {$includePath} (resolved to: {$resolvedPath})");
            }

            $this->compileFile($resolvedPath, $currentFile);
        }
    }

    /**
     * Process routes from a file
     */
    private function processRoutes(array $routes, string $sourceFile): void
    {
        $flatRoutes = $this->flattenRoutes($routes, '', [], $sourceFile);

        foreach ($flatRoutes as $route) {
            $route['_source_file'] = $sourceFile; // Track source for comments
            $this->compiledRoutes[] = $route;
        }
    }

    /**
     * Flatten nested routes into flat array
     */
    private function flattenRoutes(array $routes, string $pathPrefix = '', array $inheritedConfig = [], string $sourceFile = ''): array
    {
        $flatRoutes = [];

        // Extract inherited configuration
        $middleware = $inheritedConfig['middleware'] ?? [];
        $domain = $inheritedConfig['domain'] ?? null;
        $options = $inheritedConfig['options'] ?? [];

        // Check for configuration at this level
        if (isset($routes['middleware'])) {
            $middleware = $this->mergeMiddleware($middleware, $routes['middleware']);
            unset($routes['middleware']);
        }

        if (isset($routes['domain'])) {
            $domain = $routes['domain'];
            unset($routes['domain']);
        }

        if (isset($routes['options'])) {
            $options = array_merge($options, $routes['options']);
            unset($routes['options']);
        }

        // Handle nested includes - they inherit the current path prefix and config
        if (isset($routes['include'])) {
            $currentDir = dirname($sourceFile);
            foreach ($routes['include'] as $includePath) {
                $resolvedPath = $this->resolvePath($includePath, $currentDir);

                if (!file_exists($resolvedPath)) {
                    throw new \RuntimeException("Included file not found: {$includePath} (resolved to: {$resolvedPath})");
                }

                // Track dependency
                $resolvedPath = realpath($resolvedPath);
                $sourceFile = realpath($sourceFile);
                if (!isset($this->dependencies[$sourceFile])) {
                    $this->dependencies[$sourceFile] = [];
                }
                if (!in_array($resolvedPath, $this->dependencies[$sourceFile])) {
                    $this->dependencies[$sourceFile][] = $resolvedPath;
                }

                // Process the include file with current prefix and config
                $includedRoutes = $this->processIncludeFile($resolvedPath, $pathPrefix, [
                    'middleware' => $middleware,
                    'domain' => $domain,
                    'options' => $options,
                ]);

                $flatRoutes = array_merge($flatRoutes, $includedRoutes);
            }
            unset($routes['include']);
        }

        $currentConfig = [
            'middleware' => $middleware,
            'domain' => $domain,
            'options' => $options,
        ];

        // Process routes
        foreach ($routes as $key => $value) {
            if ($this->isHttpMethod($key)) {
                // Handle method definition
                $route = $this->buildRoute($key, $pathPrefix, $value, $currentConfig);
                $flatRoutes[] = $route;
            } elseif ($this->isPath($key)) {
                // Handle nested path
                $fullPath = $pathPrefix . $key;

                if (is_string($value)) {
                    // Simple path: /path => Handler
                    $route = $this->buildRoute('GET', $fullPath, $value, $currentConfig);
                    $flatRoutes[] = $route;
                } elseif (is_array($value)) {
                    // Nested routes - pass sourceFile for nested includes
                    $nestedRoutes = $this->flattenRoutes($value, $fullPath, $currentConfig, $sourceFile);
                    $flatRoutes = array_merge($flatRoutes, $nestedRoutes);
                }
            } elseif (is_numeric($key) && is_array($value)) {
                // Array of routes (grouping without path)
                $nestedRoutes = $this->flattenRoutes($value, $pathPrefix, $currentConfig, $sourceFile);
                $flatRoutes = array_merge($flatRoutes, $nestedRoutes);
            }
        }

        return $flatRoutes;
    }

    /**
     * Process an include file and return its routes with prefix and config
     */
    private function processIncludeFile(string $file, string $pathPrefix, array $inheritedConfig): array
    {
        $file = realpath($file);

        // Track this file as a source file if not already tracked
        if (!isset($this->sourceFiles[$file])) {
            $this->sourceFiles[$file] = filemtime($file);
        }

        $content = file_get_contents($file);
        $data = Yaml::parse($content);

        if (!is_array($data)) {
            throw new \RuntimeException("Invalid YAML in included file: {$file}");
        }

        $flatRoutes = [];

        // Handle top-level includes in the included file
        if (isset($data['include'])) {
            $currentDir = dirname($file);
            foreach ($data['include'] as $includePath) {
                $resolvedPath = $this->resolvePath($includePath, $currentDir);

                if (!file_exists($resolvedPath)) {
                    throw new \RuntimeException("Included file not found: {$includePath} (resolved to: {$resolvedPath})");
                }

                // Track dependency
                $resolvedPath = realpath($resolvedPath);
                if (!isset($this->dependencies[$file])) {
                    $this->dependencies[$file] = [];
                }
                if (!in_array($resolvedPath, $this->dependencies[$file])) {
                    $this->dependencies[$file][] = $resolvedPath;
                }

                $nestedRoutes = $this->processIncludeFile($resolvedPath, $pathPrefix, $inheritedConfig);
                $flatRoutes = array_merge($flatRoutes, $nestedRoutes);
            }
        }

        // Process routes from this file
        if (isset($data['routes'])) {
            $routes = $this->flattenRoutes($data['routes'], $pathPrefix, $inheritedConfig, $file);
            $flatRoutes = array_merge($flatRoutes, $routes);
        }

        return $flatRoutes;
    }

    /**
     * Build a single route definition
     */
    private function buildRoute(string $method, string $path, $handler, array $config): array
    {
        $route = [
            'method' => $method,
            'path' => $path,
            'middleware' => $config['middleware'],
            'domain' => $config['domain'],
            'options' => $config['options'],
        ];

        // Handle simple string handler or complex config
        if (is_string($handler)) {
            $route['handler'] = $handler;
            $route['name'] = null;
        } elseif (is_array($handler)) {
            $route['handler'] = $handler['handler'] ?? '';
            $route['name'] = $handler['name'] ?? null;

            // Method-specific middleware
            if (isset($handler['middleware'])) {
                $route['middleware'] = $this->mergeMiddleware(
                    $route['middleware'],
                    $handler['middleware']
                );
            }
        }

        return $route;
    }

    /**
     * Merge middleware arrays, handling removal with - prefix
     */
    private function mergeMiddleware(array $existing, array $new): array
    {
        $result = $existing;

        foreach ($new as $middleware) {
            if (strpos($middleware, '-') === 0) {
                // Remove middleware
                $toRemove = substr($middleware, 1);
                $result = array_values(array_filter($result, function($m) use ($toRemove) {
                    return $m !== $toRemove;
                }));
            } else {
                // Add middleware if not already present
                if (!in_array($middleware, $result)) {
                    $result[] = $middleware;
                }
            }
        }

        return $result;
    }

    /**
     * Build final array with metadata
     */
    private function buildFinalArray(): array
    {
        $result = [
            '_meta' => [
                'generated_at' => time(),
                'source_files' => $this->sourceFiles,
                'dependencies' => $this->dependencies,
            ],
        ];

        // Group routes by source file
        $routesByFile = [];
        foreach ($this->compiledRoutes as $route) {
            $file = $route['_source_file'];
            unset($route['_source_file']);

            if (!isset($routesByFile[$file])) {
                $routesByFile[$file] = [];
            }
            $routesByFile[$file][] = $route;
        }

        // Add routes with source comments
        foreach ($routesByFile as $file => $routes) {
            foreach ($routes as $route) {
                $route['_source_comment'] = $file;
                $result[] = $route;
            }
        }

        return $result;
    }

    /**
     * Generate PHP code from compiled routes
     */
    private function generatePhpCode(array $routes): string
    {
        $meta = $routes['_meta'];
        unset($routes['_meta']);

        $php = "<?php\n";
        $php .= "/**\n";
        $php .= " * Auto-generated route definitions\n";
        $php .= " * Generated: " . date('Y-m-d H:i:s', $meta['generated_at']) . "\n";
        $php .= " * \n";
        $php .= " * DO NOT EDIT THIS FILE DIRECTLY\n";
        $php .= " * Edit source YAML files and run: php console routes:compile\n";
        $php .= " * \n";
        $php .= " * Source files compiled:\n";

        foreach ($meta['source_files'] as $file => $mtime) {
            $php .= " *   - {$file} (modified: " . date('Y-m-d H:i:s', $mtime) . ")\n";
        }

        $php .= " */\n\n";
        $php .= "return [\n";

        // Add metadata
        $php .= "\t'_meta' => " . $this->varExportPretty($meta, 1) . ",\n\n";

        // Add routes with comments
        $lastSourceFile = null;
        foreach ($routes as $route) {
            $sourceFile = $route['_source_comment'] ?? null;
            unset($route['_source_comment']);

            // Add source file comment
            if ($sourceFile !== $lastSourceFile) {
                if ($lastSourceFile !== null) {
                    $php .= "\n";
                }
                $php .= "\t// " . str_repeat('=', 60) . "\n";
                $php .= "\t// Routes from: {$sourceFile}\n";

                // Check if this file was included by another
                foreach ($meta['dependencies'] as $parent => $children) {
                    if (in_array($sourceFile, $children)) {
                        $php .= "\t// Included by: {$parent}\n";
                        break;
                    }
                }

                $php .= "\t// " . str_repeat('=', 60) . "\n";
                $lastSourceFile = $sourceFile;
            }

            $php .= "\t" . $this->varExportPretty($route, 1) . ",\n";
        }

        $php .= "];\n";

        return $php;
    }

    /**
     * Pretty var_export for arrays
     */
    private function varExportPretty($var, int $indent = 0): string
    {
        $indentStr = str_repeat("\t", $indent);

        if (is_array($var)) {
            if (empty($var)) {
                return '[]';
            }

            $isAssoc = array_keys($var) !== range(0, count($var) - 1);
            $items = [];

            foreach ($var as $key => $value) {
                $keyStr = $isAssoc ? var_export($key, true) . ' => ' : '';
                $valueStr = $this->varExportPretty($value, $indent + 1);
                $items[] = "{$indentStr}\t{$keyStr}{$valueStr}";
            }

            return "[\n" . implode(",\n", $items) . "\n{$indentStr}]";
        }

        return var_export($var, true);
    }

    /**
     * Resolve file path (relative or absolute)
     */
    private function resolvePath(string $path, string $relativeTo): string
    {
        // Absolute path
        if ($path[0] === '/') {
            return $path;
        }

        // Relative path
        return realpath($relativeTo . '/' . $path);
    }

    /**
     * Check if string is an HTTP method
     */
    private function isHttpMethod(string $str): bool
    {
        return in_array($str, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD']);
    }

    /**
     * Check if string is a path
     */
    private function isPath(string $str): bool
    {
        return isset($str[0]) && $str[0] === '/';
    }

    /**
     * Get all source files including includes
     */
    private function getAllSourceFiles(array $entryFiles): array
    {
        $allFiles = [];

        foreach ($entryFiles as $file) {
            $this->collectSourceFiles($file, $allFiles);
        }

        return array_unique($allFiles);
    }

    /**
     * Recursively collect all source files
     */
    private function collectSourceFiles(string $file, array &$files): void
    {
        $file = realpath($file);

        if (!$file || in_array($file, $files)) {
            return;
        }

        $files[] = $file;

        // Parse file to find includes
        $content = file_get_contents($file);
        $data = Yaml::parse($content);

        if (isset($data['include'])) {
            $currentDir = dirname($file);
            foreach ($data['include'] as $includePath) {
                $resolvedPath = $this->resolvePath($includePath, $currentDir);
                $this->collectSourceFiles($resolvedPath, $files);
            }
        }

        // Check for nested includes in routes
        if (isset($data['routes'])) {
            $this->collectNestedIncludes($data['routes'], dirname($file), $files);
        }
    }

    /**
     * Collect includes nested within routes
     */
    private function collectNestedIncludes(array $routes, string $currentDir, array &$files): void
    {
        foreach ($routes as $key => $value) {
            if ($key === 'include' && is_array($value)) {
                foreach ($value as $includePath) {
                    $resolvedPath = $this->resolvePath($includePath, $currentDir);
                    $this->collectSourceFiles($resolvedPath, $files);
                }
            } elseif (is_array($value) && !$this->isHttpMethod($key)) {
                // Recursively check nested routes, but skip HTTP method arrays
                $this->collectNestedIncludes($value, $currentDir, $files);
            }
        }
    }

    /**
     * Build file tree for reporting
     */
    private function buildFileTree(string $file, array $compiledMeta, array $dependencies, int $level = 0): array
    {
        $file = realpath($file) ?: $file;
        $exists = file_exists($file);
        $mtime = $exists ? filemtime($file) : null;
        $compiled = isset($compiledMeta[$file]) ? $compiledMeta[$file] : null;
        $outdated = $exists && $compiled && $mtime > $compiled;

        $node = [
            'file' => $file,
            'exists' => $exists,
            'modified' => $mtime,
            'compiled' => $compiled,
            'outdated' => $outdated,
            'level' => $level,
            'children' => [],
        ];

        // Add children (includes)
        if (isset($dependencies[$file])) {
            foreach ($dependencies[$file] as $child) {
                $node['children'][] = $this->buildFileTree($child, $compiledMeta, $dependencies, $level + 1);
            }
        }

        return $node;
    }
}
