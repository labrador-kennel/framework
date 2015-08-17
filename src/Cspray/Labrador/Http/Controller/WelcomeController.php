<?php

declare(strict_types=1);

/**
 * @license See LICENSE in source root
 */

namespace Cspray\Labrador\Http\Controller;

use Symfony\Component\HttpFoundation\Response;

class WelcomeController extends Controller {

    public function index() : Response {
        return new Response($this->getHtml());
    }

    private function getHtml() : string {
        return <<<HTML
<!DOCTYPE html>
<html>
  <head>
    <title>Labrador HTTP Application</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" />
    <style>
     main { padding: 0 .5em; }
     .margin--soft-bottom { margin-bottom: .5em; }
    </style>
  </head>
  <body>
    <header>
      <nav class="navbar navbar-inverse">
        <div class="navbar-header">
          <a class="navbar-brand" href="/">Labrador HTTP</a>
        </div>
      </nav>
    </header>

    <main class="'container-fluid">
      <div class="row">
        <section class="col-md-9">
          <p>Thanks for installing Labrador HTTP! </p>
        </section>
        <section class="col-md-3">
          <h3>References</h3>
          <ul class="list-group">
            <li class="list-group-item"><a href="https://github.com/cspray/labrador-http" class="btn btn-link btn-lg">GitHub Repository</a></li>
            <li class="list-group-item"><a href="https://github.com/cspray/labrador-http/wiki" class="btn btn-link btn-lg">Repo's Wiki</a></li>
            <li class="list-group-item"><a href="https://github.com/cspray/labrador/wiki" class="btn btn-link btn-lg">Labrador Wiki</a></li>
          </ul>
          <h3>Dependent Libraries</h3>
          <dl>
            <dt><a href="https://github.com/cspray/labrador">Labrador</a></dt>
            <dd class="margin--soft-bottom">Microframework providing "low-level" functionality powering the HTTP Engine.</dd>
            <dt><a href="https://github.com/rdlowrey/Auryn">Auryn</a></dt>
            <dd class="margin--soft-bottom">An awesome <a href="https://en.wikipedia.org/wiki/Inversion_of_control">IoC</a> container that we use to wire up our object graph.</dd>
            <dt><a href="https://github.com/nikic/FastRoute">FastRoute</a></dt>
            <dd class="margin--soft-bottom">A simple, performant routing library underpinning Labrador's routing system.</dd>
            <dt><a href="">Symfony HTTP Foundation</a></dt>
            <dd class="margin--soft-bottom">A well-known, highly-used library to abstract the <a href="https://en.wikipedia.org/wiki/Request%E2%80%93response">HTTP Request/Response cycle</a>.</dd>
          </dl>
        </section>
      </div>
    </main>


    <script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
  </body>
</html>
HTML;

    }

}
