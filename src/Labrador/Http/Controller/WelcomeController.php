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
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css" rel="stylesheet" />
    </head>
    <body>
        <header>
            <h1>Labrador HTTP Application</h1>
        </header>
        <section id="main">
            <p>Welcome to <a href="https://github.com/cspray/labrador-http">Labrador HTTP</a>, a microframework written on top of <a href="">Labrador</a>.</p>
        </section>
        <footer>

        </footer>
    </body>
</html>

HTML;

    }

}
