<?php

namespace Cspray\Labrador\Http\Middleware;

enum Priority {
    case Critical;
    case High;
    case Medium;
    case Low;
}
