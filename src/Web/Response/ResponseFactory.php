<?php declare(strict_types=1);

namespace Labrador\Web\Response;

use Amp\Http\HttpMessage;
use Amp\Http\HttpStatus;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\Response;
use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\Template\RenderedTemplate;
use Labrador\Web\Response\Exception\AmbiguousRedirectLocation;
use Psr\Http\Message\UriInterface;

/**
 * @psalm-import-type HeaderParamArrayType from HttpMessage
 */
#[Service]
final class ResponseFactory {

    public function __construct(
        private readonly ErrorHandler $errorHandler
    ) {
    }

    /**
     * @param string|RenderedTemplate $content
     * @param HeaderParamArrayType $headers
     * @param int $status
     * @return Response
     */
    public function html(
        string|RenderedTemplate $content,
        array $headers = [],
        int $status = HttpStatus::OK
    ) : Response {
        $body = $content instanceof RenderedTemplate ? $content->toString() : $content;
        $headers = array_merge([], ['Content-Type' => 'text/html; charset=utf-8'], $headers);

        return new Response(
            status: $status,
            headers: $headers,
            body: $body
        );
    }

    /**
     * @param HeaderParamArrayType $headers
     * @throws AmbiguousRedirectLocation
     */
    public function seeOther(
        UriInterface $location,
        array $headers = [],
    ) : Response {
        foreach (array_keys($headers) as $key) {
            if (strtolower($key) === 'location') {
                throw AmbiguousRedirectLocation::fromSeeOtherResponseHasAmbiguousHeaders();
            }
        }

        return new Response(
            status: HttpStatus::SEE_OTHER,
            headers: ['Location' => (string) $location, ...$headers],
        );
    }

    /**
     * Generates a response derived from the ErrorHandler wired into your container.
     */
    public function error(int $status) : Response {
        return $this->errorHandler->handleError($status);
    }
}
