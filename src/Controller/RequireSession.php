<?php declare(strict_types=1);

namespace Labrador\Http\Controller;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class RequireSession {

    public function __construct(
        public readonly SessionAccess $access
    ) {}

}
