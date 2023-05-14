<?php declare(strict_types=1);

namespace Labrador\GettingStarted\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Labrador\Web\Controller\SelfDescribingController;

final class Home extends SelfDescribingController {

    public function handleRequest(Request $request) : Response {
        return new Response(
            headers: ['Content-Type' => 'text/html; charset=utf-8'],
            body: $this->template()
        );
    }

    private function template() : string {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Labrador Getting Started</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css"> -->
    </head>
    <body>
        <header>
            <h1>Labrador Getting Started</h1>
        </header>
        <main>
            <p>Thanks for trying Labrador!</p> 
        </main>
        <footer>
        
        </footer>
    </body>
</html>
HTML;

    }

}
