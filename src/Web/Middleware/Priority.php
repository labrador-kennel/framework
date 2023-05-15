<?php

namespace Labrador\Web\Middleware;

enum Priority {
    case Critical;
    case High;
    case Medium;
    case Low;
}
