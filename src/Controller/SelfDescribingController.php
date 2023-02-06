<?php

namespace Labrador\Http\Controller;

abstract class SelfDescribingController implements Controller {

    public function toString() : string {
        return static::class;
    }

}