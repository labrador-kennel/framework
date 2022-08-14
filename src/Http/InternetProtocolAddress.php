<?php

namespace Cspray\Labrador\Http\Http;

final class InternetProtocolAddress {

    private function __construct(
        public readonly string $ip,
        public readonly int $port,
        public readonly bool $tls
    ) {}

    public static function http(string $ip, int $port) : self {
        return new self($ip, $port, false);
    }

    public static function https(string $ip, int $port) : self {
        return new self($ip, $port, true);
    }

}