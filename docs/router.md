# Routing
 
## Setup

- Install symfony/yaml dependency
- Create app/config/routes.yaml
- Run routes:compile command, which will convert app/config/routes.yaml into a usable PHP array stored in app/config/routes.yaml.php

```shell
php console routes:compile
php console routes:compile --watch (or -w) to watch for changes
```

Both will update app/config/routes.yaml.php.  See below for overriding file.

```php
$config = new \WebImage\Config\Config(require(__DIR__ . '/../app/config/config.php'));
$app = new \WebImage\Application\HttpApplication($config);

// Optional: Manually load routes if you need custom logic
// $app->loadRoutesFromFile(__DIR__ . '/../app/config/custom-routes.yaml.php');

$app->run();
```

## Config Override Examples
Disable auto-loading (manual control):

```php
return [
    'router' => [
        'autoLoad' => false, // You'll load routes manually in index.php
    ],
];
```

## Custom file locations:

```php
// config.php
return [
    'router' => [
        'routeFiles' => [
            __DIR__ . '/routes.yaml',
            __DIR__ . '/api-routes.yaml',
        ],
        'compiledFile' => __DIR__ . '/compiled/all-routes.php',
    ],
];
```

## Require routes (throw if error missing)
```php
// config.php
return [
    'router' => [
        'required' => true, // Throw exception if compiled routes don't exist
    ],
];
```

## YAML Include Syntax
```yaml
# /app/config/routes.yaml

# Include other route files
routes:
  # Simple route
  /:
    GET: HomeController@index

  # Nested with middleware
  /admin:
    middleware: [auth, admin]

    /users:
      GET: AdminController@listUsers

      /{id}:
        GET: AdminController@showUser
        PUT: AdminController@updateUser

  # Include other files
  include:
    - ./auth-routes.yaml
    - ./api-routes.yaml
```