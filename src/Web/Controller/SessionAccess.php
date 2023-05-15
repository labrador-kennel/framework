<?php declare(strict_types=1);

namespace Labrador\Web\Controller;

enum SessionAccess {
    case Read;
    case Write;
}
