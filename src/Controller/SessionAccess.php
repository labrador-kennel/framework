<?php declare(strict_types=1);

namespace Labrador\Http\Controller;

enum SessionAccess {
    case Read;
    case Write;
}
