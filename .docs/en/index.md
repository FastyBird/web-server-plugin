# Quick start

The purpose of this extension is to create php base web server for serving and handling API request and responses.

## Installation

The best way to install **fastybird/web-server** is using [Composer](http://getcomposer.org/):

```sh
composer require fastybird/web-server
```

After that, you have to register extension in *config.neon*.

```neon
extensions:
    fbWebServer: FastyBird\WebServer\DI\WebServerExtension(%consoleMode%)
```

This extension is dependent on other extensions, and they have to be registered too

```neon
extensions:
    ...
    contributteConsole: Contributte\Console\DI\ConsoleExtension(%consoleMode%)
    contributteEvents: Contributte\EventDispatcher\DI\EventDispatcherExtension
    fbSocketServerFactory: FastyBird\SocketServerFactory\DI\SocketServerFactoryExtension
```

> For information how to configure these extensions please visit their doc pages

## Configuration

This extension has some configuration options:

```neon
fbWebServer:
    static:
        webroot: /path/to/public/folder
        enabled: false

fbSocketServerFactory:
    server:
        address: 127.0.0.1
        port: 8000
        certificate: /path/to/your/certificate.pa
```

Where:

- `static -> webroot` is path to public static files and this files could be served by this webserver
- `static -> enabled` enable or disable serving static files support

And settings for socket server extension:

- `server -> address` is address where is server listening for incoming requests
- `server -> port` is address port where is server listening for incoming requests
- `server -> certificate` is path to your private certificate to enable SSL communication

## Application routes

This extension has router collector services which will search for your custom routes providers and register routes to
the server service.

All what you have to do is create routes provider service and configure routes and their controllers:

```php
namespace Your\CoolApp\Routing;

use FastyBird\WebServer\Router;
use IPub\SlimRouter\Routing;

use Your\CoolApp\Controllers;

class Routes implements Router\IRoutes
{

    /** @var Controllers\ArticlesController */
    private Controllers\ArticlesController $articlesV1Controller;

    public function __construct(
        Controllers\ArticlesController $articlesV1Controller
    ) {
        $this->articlesV1Controller = $articlesV1Controller;
    }

    public function registerRoutes(Routing\IRouter $router): void
    {
        return $router->group('/v1', function (Routing\RouteCollector $group): void {
            $group->group('/articles', function (Routing\RouteCollector $group): void {
                $group->get('', [$this->articlesV1Controller, 'index']);
    
                $group->get('/{id}', [$this->articlesV1Controller, 'read']);
    
                $group->post('', [$this->articlesV1Controller, 'create']);
    
                $group->patch('/{id}', [$this->articlesV1Controller, 'update']);
    
                $group->delete('/{id}', [$this->articlesV1Controller, 'delete']);
    
                $group->get('/{id}/relationships/{relation}', [
                    $this->articlesV1Controller,
                    'readRelationship',
                ]);
            });
        });
    }

}
```

For more info how to write routes and controllers please
visit: [ipub/slim-router](https://github.com/iPublikuj/slim-router/blob/master/docs/en/index.md) package documentation

## Custom middleware

With middleware, you could modify incoming request and also outgoing response.

```php
namespace Your\CoolApp\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AccessControlMiddleware implements MiddlewareInterface
{

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Do you logic here eg. modify request

        // With $handler call another middleware
        $response = $handler->handle($request);

        // Do another logic eg. modify response

        return $response;
    }

}
```

Each middleware have to be registered as a service with optional priority configuration

```neon
services:
    - {factory: Your\CoolApp\AccessControlMiddleware, tags: [middleware: {priority: 30}]}
```

Extension will search for all middleware which are registered and register them to the router service. This type of
middleware will be used for each route. But there could be cases where you want use your middleware for specific routes
only.

It that case you could create middleware like shown above, but omit tag configuration in neon

```neon
services:
    - {factory: Your\CoolApp\AccessControlMiddleware}
```

Middleware will be registered as usual service and could be injected into router, where you could add it to specific
route.

```php
namespace Your\CoolApp\Routing;

use FastyBird\WebServer\Router;
use IPub\SlimRouter\Routing;

class Routes implements Router\IRoutes
{
    // ...

    /** @var AccessControlMiddleware */
    private AccessControlMiddleware $accessControlMiddleware;

    public function registerRoutes(Routing\IRouter $router): void
    {
        // ...

            $deleteRoute = $group->delete('/{id}', [$this->articlesV1Controller, 'delete']);
            $deleteRoute->addMiddleware($this->accessControlMiddleware);

        // ...
    }
}
```

For more info how to write middleware please
visit: [ipub/slim-router](https://github.com/iPublikuj/slim-router/blob/master/docs/en/index.md) package documentation

## Running server

To be able to start server, you have to create an entrypoint for console. It is a simple script that loads the DI
container and fires `FastyBird\WebServer\Application\Console::run`.

You can copy & paste it to your project, for example to `<app_root>/bin/console`.

Make sure to set it as executable:

```sh
chmod +x <app_root>/bin/console
```

```php
#!/usr/bin/env php
<?php declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

exit(Your\CoolApp\Bootstrap::boot()
    ->createContainer()
    ->getByType(FastyBird\WebServer\Application\Console::class)
    ->run());
```

To start serving content and handling request just start server with command:

```sh
<app_root>/console
```

## What about Apache or Nginx?

If you have any reason to use classic web server like [Apache](https://www.apache.org) or [Nginx](https://www.nginx.com)
, this extension has solution for you.

Steps to achieve this way is almost same as in console version. You have to create an entrypoint which will loads DI and
fire `FastyBird\WebServer\Application\Application::run`

You can copy & paste it to your project, for example to `<app_root>/www/index.php`.

```php
<?php declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

exit(Your\CoolApp\Bootstrap::boot()
    ->createContainer()
    ->getByType(FastyBird\WebServer\Application\Application::class)
    ->run());
```

And as a last step, you have to configure you Apache or Nginx server to load your page from: `<app_root>/www/index.php`

# Tips

If you want to use [{JSON:API}](https://jsonapi.org/) for you api calls, you could
use [fastybird/json-api](https://github.com/FastyBird/json-api) package. This package brings you schemas factory for
your responses and document to entity hydrator

And last but not least, [fastybird/simple-auth](https://github.com/FastyBird/simple-auth). With this package you could
create basic token based authentication and authorization.

***
Homepage [https://www.fastybird.com](https://www.fastybird.com) and
repository [https://github.com/FastyBird/web-server](https://github.com/FastyBird/web-server).
