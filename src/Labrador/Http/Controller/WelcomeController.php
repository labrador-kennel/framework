<?php

/**
 * 
 * @license See LICENSE in source root
 * @version 1.0
 * @since   1.0
 */

namespace Labrador\Http\Controller;

use Symfony\Component\HttpFoundation\Response;

class WelcomeController extends RenderingController {

    public function index() {
        $this->set('css', ['https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css']);
        $content = $this->render('welcome/index');
        return new Response($content);
    }

}
