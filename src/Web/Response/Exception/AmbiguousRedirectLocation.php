<?php declare(strict_types=1);

namespace Labrador\Web\Response\Exception;

use Amp\Http\HttpStatus;
use Labrador\Exception\Exception;
use Psr\Http\Message\UriInterface;

class AmbiguousRedirectLocation extends Exception {

    public static function fromSeeOtherResponseHasAmbiguousHeaders() : self {
        return new self(sprintf(
            'Attempted to create a %s Response but multiple location targets were provided. Do not include a Location' .
            ' header when creating this type of response.',
            HttpStatus::SEE_OTHER,
        ));
    }
}
