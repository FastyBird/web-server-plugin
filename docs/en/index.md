# Quick start

This extension adds support for [ReactPHP](https://github.com/reactphp/http) web server.

## Installation

The best way to install **fastybird/web-server** is using [Composer](http://getcomposer.org/):

```sh
composer require fastybird/web-server
```

After that you have to register extension in *config.neon*.

```neon
extensions:
    fbWebServer: FastyBird\WebServer\DI\WebServerExtension
```

## Configuration

This extension has some configuration options:

```neon
fbWebServer:
    server:
        address: 127.0.0.1
        port: 8000
        certificate: /path/to/your/certificate.pa
    static:
        webroot: /path/to/public/folder
        enabled: false
```

Where:

- `server->address` is address where is server listening for incoming requests
- `server->port` is address port where is server listening for incoming requests
- `server->certificate` is path to your private certificate to enable SSL communication


- `static->webroot` is path to public static files and this files could be served by this webserver
- `static->enabled` enable or disable serving static files support

## Run server

To start serving content and handling request just start server with command:

```sh
vendor/bin/fb-webserver fb:web-server:start
```