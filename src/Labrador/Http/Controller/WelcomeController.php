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
    <style>
    body {
        color: rgba(0, 0, 0, .75);
    }

    .panel {
        padding: .5em;
    }

    .panel--primary-color { background-color: rgb(137, 89, 77); color: white; }
    .panel--secondary-color { background-color: rgb(243, 229, 211); }
    .panel--shadow { box-shadow: 0 0 2px rgba(0,0,0,.12),0 2px 4px rgba(0,0,0,.24); }
    .panel--page-break { height: 2px; margin: 2em .5em; }

    .text--primary-color { color: rgb(137, 89, 77); }

    .padding--1 { padding: 1em; }

    .no-margin { margin: 0; }
    .no-margin--top { margin-top: 0; }
    .no-margin--bottom { margin-bottom: 0; }
    .no-margin--left { margin-left: 0; }
    .no-margin--right { margin-right: 0; }

    .margin--1 { margin: 1em; }
    .margin--1--top { margin-top: 1em; }
    .margin--1--bottom { margin-bottom: 1em; }
    .margin--1--left { margin-left: 1em; }
    .margin--1--right { margin-right: 1em; }

    .margin--1-2 { margin: .5em; }
    .margin--1-2--top { margin-top: .5em; }
    .margin--1-2--bottom { margin-bottom: .5em; }
    .margin--1-2--left { margin-left: .5em; }
    .margin--1-2--right { margin-left: .5em; }

    .no-border-radius { border-radius: 0; }

    .panel--quote {
        padding: 2em 0;
    }

    .hero-panel {
        padding: 1.5em 0;
    }

    main {
        font-size: 125%;
    }

    .coolness-list {
        margin: 0;
        padding: 0;
        width: 100%;
    }

    .coolness-list--card-title {
        padding-bottom: .1em;
        border-bottom: 2px solid rgb(137, 89, 77);
    }

    .coolness-list--card-description {
        color: rgba(0, 0, 0, .65);
    }
    </style>
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

      <div class="panel--page-break panel--primary-color"></div>

      <section class="clearfix guides-list">
        <section id="routing" class="panel">
          <header>
            <h1>Routing</h1>
          </header>
          <div>
            <p>Labrador's routing is similar to most microframeworks. We map an
            HTTP method and URL to a piece of data that can be resolved to a
            callable function.</p>
          </div>
        </section>
      </section>
    </main>

    <script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
  </body>
</html>
HTML;

    }

}
