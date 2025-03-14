<?php declare(strict_types=1);

namespace Labrador\Web\Middleware;

use Amp\Http\Server\Middleware;
use ArrayIterator;
use Cspray\AnnotatedContainer\Attribute\Service;
use IteratorAggregate;
use Traversable;

/**
 * @template-implements IteratorAggregate<int, Middleware>
 */
#[Service]
final class GlobalMiddlewareCollection implements IteratorAggregate {

    private array $middleware = [];

    public function add(Middleware $middleware) : void {
        $this->middleware[] = $middleware;
    }

    public function getIterator() : Traversable {
        return new ArrayIterator($this->middleware);
    }
}
