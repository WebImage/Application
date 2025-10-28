<?php

namespace WebImage\Route;

use WebImage\Core\ArrayHelper;
use WebImage\Core\Collection;

class ArrayRouteLoader implements RouteLoaderInterface
{
    /**
     * @param array $aRoutes
     * @return RouteInfoCollection
     */
    public function load(array $aRoutes): RouteInfoCollection
    {
        // Remove metadata if present (from compiled YAML routes)
        if (isset($aRoutes['_meta'])) {
            unset($aRoutes['_meta']);
        }

        // Filter out routes with _source_comment (internal compiler metadata)
        $aRoutes = array_map(function($route) {
            if (is_array($route) && isset($route['_source_comment'])) {
                unset($route['_source_comment']);
            }
            return $route;
        }, $aRoutes);

        $routes = new RouteInfoCollection();
        $this->recursivelyInjectRoutes($routes, $aRoutes, 'root');
        return $routes;
    }

    private function recursivelyInjectRoutes(Collection $routes, array $aRoutes, string $pathHint, array $info=[], int $depth=1): void
    {
        list($aRoutes, $info) = $this->normalizeRoutes($aRoutes, $info, $pathHint);
        $middlewares = $info['middleware'] ?? [];
        $prefix      = $info['prefix'] ?? '';

        foreach($aRoutes as $mountPoint => $routeData) {
            if ($routeData === null) {
                throw new \RuntimeException('Invalid route definition at ' . $pathHint . '[' . $mountPoint . '] was expecting an array');
            } else if (is_numeric($mountPoint)) { // An array of values simply meant to group routes
                if (!is_array($routeData)) {
                    throw new \RuntimeException('Invalid route definition at ' . $pathHint . '[' . $mountPoint . '] was expecting an array');
                }

                // Handle flat route format from YAML compiler
                if ($this->isFlatRouteDefinition($routeData)) {
                    $method = $routeData['method'] ?? 'GET';
                    $path = $routeData['path'] ?? $prefix;
                    $handler = $routeData['handler'] ?? '';
                    $middleware = array_merge($middlewares, $routeData['middleware'] ?? []);
                    $name = $routeData['name'] ?? null;
                    $domain = $routeData['domain'] ?? null;

                    $route = new RouteInfo($method, $path, $handler, $middleware, $name);
                    $routes->add($route);
                } else {
                    $this->recursivelyInjectRoutes($routes, $routeData, $pathHint . '[' . $mountPoint . ']', $info, $depth + 1);
                }
            } else if (in_array($mountPoint, ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH', 'HEAD'])) { // Handle METHOD-based routes
                if (is_string($routeData)) {
                    list($method, $path, $handler) = [$mountPoint, $prefix, $routeData];
                    $routes->add(new RouteInfo($method, $path, $handler, $middlewares));
                } else if (is_array($routeData) && ArrayHelper::isAssociative($routeData, true)) {
                    ArrayHelper::assertKeys($routeData, $pathHint . '[' . $mountPoint . ']', ['handler'], ['name']);
                    $handler = ArrayHelper::get($routeData, 'handler');
                    $name = ArrayHelper::get($routeData, 'name');
                    list($method, $path) = [$mountPoint, $prefix];
                    $routes->add(new RouteInfo($method,  $prefix, $handler, $middlewares, $name));
                } else {
                    throw new \RuntimeException('Unsupported path type for path: ' . $mountPoint . ' ' . $prefix . ' ' . gettype($routeData));
                }
            } else if (substr($mountPoint, 0, 1) == '/') { // Handle standard route definition
                if (is_string($routeData)) {
                    list($path, $handler) = [$prefix . $mountPoint, $routeData];
                    $routes->add(new RouteInfo('GET', $path, $handler, $middlewares));
                } else {
                    $info['prefix'] = $prefix . $mountPoint;

                    $this->recursivelyInjectRoutes($routes, $routeData, $pathHint . '[' . $mountPoint . ']', $info, $depth + 1);
                }
            } else {
                throw new \RuntimeException('Invalid route mount point: ' . $mountPoint);
            }
        }
    }

    /**
     * Check if array is a flat route definition from YAML compiler
     */
    private function isFlatRouteDefinition(array $data): bool
    {
        return isset($data['method']) && isset($data['path']) && isset($data['handler']);
    }

    /**
     * Remove "middleware" key from $routes and add a merged copy of it to $info['middleware']
     * @param array $aRoutes
     * @param array $info
     * @param string $pathHint
     * @return array
     */
    private function normalizeRoutes(array $aRoutes, array $info, string $pathHint): array
    {
        if (!array_key_exists('middleware', $aRoutes)) return [$aRoutes, $info];

        $info['middleware'] = $info['middleware'] ?? [];

        foreach($aRoutes['middleware'] as $middleware) {
            if (substr($middleware, 0, 1) == '-') { // Remove previously established middleware
                $middleware = substr($middleware, 1);
                $info['middleware'] = $this->removeMiddleware($info['middleware'], $middleware, $pathHint);
            } else {
                $info['middleware'] = $this->addMiddleware($info['middleware'], $middleware, $pathHint);
            }
        }

        unset($aRoutes['middleware']);

        return [$aRoutes, $info];
    }

    private function removeMiddleware(array $middlewares, string $middleware, string $pathHint): array
    {
        $ix = array_search($middleware, $middlewares);
        if ($ix !== false) {
            array_splice($middlewares, $ix, 1);
        } else {
            throw new \RuntimeException('Trying to remove middleware that does not exist at ' . $pathHint . '[middleware]: ' . $middleware);
        }

        return $middlewares;
    }

    private function addMiddleware(array $middlewares, string $middleware, string $pathHint): array
    {
        if (in_array($middleware, $middlewares)) throw new \RuntimeException('Adding middleware ' . $middleware . ' at ' . $pathHint . '[middleware], but ' . $middleware . ' was already added at a higher level.');
        $middlewares[] = $middleware;

        return $middlewares;
    }
}
