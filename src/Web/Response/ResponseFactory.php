<?php declare(strict_types=1);

namespace Labrador\Web\Response;

use Amp\Http\HttpMessage;
use Amp\Http\HttpStatus;
use Amp\Http\Server\Response;
use Cspray\AnnotatedContainer\Attribute\Service;
use Labrador\Template\RenderedTemplate;

/**
 * @psalm-import-type HeaderParamArrayType from HttpMessage
 */
#[Service]
final class ResponseFactory {

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

}
