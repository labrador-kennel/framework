<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Labrador\Http\Controller;

use Symfony\Component\HttpFoundation\Response;

class WelcomeController {

    public function index() {
        return new Response($this->getHtml());
    }

    private function getHtml() {
        return <<<HTML
<!DOCTYPE html>
<html>
    <head>
        <title>Labrador HTTP Application</title>
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" />
        <link href="/css/main.css" rel="stylesheet" />
    </head>
    <body>
        <header class="panel--primary-color no-margin padding--1">
            <h1 class="no-margin">Labrador HTTP Application</h1>
        </header>
        <main>
            <section class="hero-panel panel panel--secondary-color text-center no-margin no-border-radius">
                <h1 class="no-margin">
                  <i class="fa fa-check-circle text-success margin--1-2--bottom"></i>
                  Install OK!
                </h1>
                <p>
                Thanks for installing Labrador HTTP! Check out all the coolness you have
                available to you and next steps.
                </p>
                <a class="btn btn-lg btn-default" href="https://github.com/cspray/labrador/wiki">
                    <i class="fa fa-book"></i> Read the wiki for more info!
                </a>
            </section>

            <section class="panel panel--quote text-center panel--primary-color no-border-radius">
                <h1>A microframework written for PHP 5.6+</h1>
            </section>

            <section class="container-fluid clearfix coolness-list">
                <div class="col-sm-4">
                    <div class="panel panel--secondary-color panel--shadow">
                        <h2 class="coolness-list--card-title no-margin--top">Routing</h2>
                        <p class="coolness-list--card-description">
                        Learn more about how Labrador allows you to route requests to
                        a controller of your choosing. Under the hood we use
                        <a href="https://github.com/nikic/FastRoute">FastRoute</a>.
                        </p>
                        <div class="text-center">
                            <a class="btn btn-lg btn-default" href="#routing">Learn more!</a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="panel panel--secondary-color panel--shadow">
                        <h2 class="coolness-list--card-title no-margin--top">Plugins</h2>
                        <p class="coolness-list--card-description">
                        Learn more about how you can create custom code that plugs into
                        Labrador and your application via timely triggered events.
                        </p>
                        <div class="text-center">
                            <a class="btn btn-lg btn-default" href="#routing">Learn more!</a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-4">
                    <div class="panel panel--secondary-color panel--shadow">
                        <h2 class="coolness-list--card-title no-margin--top">IoC</h2>
                        <p class="coolness-list--card-description">
                        Not a traditional DIC and definitely not a Service Locator.
                        Learn how to use <a href="https://github.com/rdlowrey/Auryn">Auryn</a>
                        and Labrador to easily manage dependencies.
                        </p>
                        <div class="text-center">
                            <a class="btn btn-lg btn-default" href="#routing">Learn more!</a>
                        </div>
                    </div>
                </div>
            </section>
        </main>
        <footer>

        </footer>

        <script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
    </body>
</html>
HTML;

    }

}
