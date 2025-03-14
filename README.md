# Labrador Framework

[![GitHub license](https://img.shields.io/github/license/labrador-kennel/http.svg?style=flat-square)](http://opensource.org/licenses/MIT)
[![GitHub release](https://img.shields.io/github/release/labrador-kennel/http.svg?style=flat-square)](https://github.com/labrador-kennel/http/releases/latest)

> Labrador is still in heavy development. A 1.0.0-alpha release is near but the API is still highly volatile and subject to change!

Labrador is a microframework built on-top of [Amphp](https://github.com/amphp) and [Annotated Container](https://github.com/cspray/annotated-container). It offers a non-traditional way of writing small-to-medium sized applications in PHP. Among its many features includes:

- Everything in pure PHP. Have a fully-featured web server up with `php app.php`
- Declarative approach to dependency injection
- Comprehensive, secure solution for handling configuration
- Asynchronous by default
- Robust, data-rich event system for knowing when things happen
- An easy-to-use HTTP and routing layer

If you're looking for a more complete skeleton to get started with writing Labrador-powered apps, you should check out [labrador-kennel/web-app](https://github.com/labrador-kennel/web-app); it is a skeleton for a complete app, including Docker setup, database, and templating.

## Install

Use [Composer](https://getcomposer.org) to install the library.

```
composer require labrador-kennel/framework
```

## Requirements 

This is a step-by-step guide to the code you'll need to implement to get this framework serving HTTP requests. This is not meant to be a comprehensive guide on how to deploy in production, but how to get up and running on your local machine.

### Step 1: Logging

A critical step that MUST be completed, otherwise your application will not start up. To satisfy this requirement you must implement a `Labrador\Logging\LoggerFactory` and mark it as a service to be managed by the dependency injection container. Somewhere in your `src/` directory, or some other directory that is autoloaded and scaned by Annotated Container, write some code that resembles the following:

```php
<?php declare(strict_types=1);

namespace App;

use Amp\Log\StreamHandler;
use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\Logging\LoggerFactory;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;
use function Amp\ByteStream\getStdout;

#[Service]
final class MyLoggerFactory implements LoggerFactory {

    public function createLogger() : LoggerInterface{
        return new Logger(
            'my-logger-name',
            [new StreamHandler(getStdout())],
            [new PsrLogMessageProcessor()]
        );
    }


}
```

This is a minimal setup that could be applicable if your app is running in a Docker container. It will stream log output to `stdout` using Amp-provided mechanisms. It is important to use a Monolog handler provided by Amp, to avoid blocking I/O operations.


Again, this is a highly important step that you MUST complete. If you get an error from your dependency injection container stating that a `LoggerFactory` cannot be instantiated, completing this step is your resolution.

> TODO: Determine and document precise steps would need to result in a running app


