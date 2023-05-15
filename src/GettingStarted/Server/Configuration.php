<?php declare(strict_types=1);

namespace Labrador\GettingStarted\Server;

use Amp\Socket\InternetAddress;
use Labrador\Web\Server\HttpServerConfiguration;

final class Configuration implements HttpServerConfiguration {

    public function getUnencryptedInternetAddresses() : array {
        return [
            new InternetAddress('127.0.0.1', 8118)
        ];
    }

    public function getEncryptedInternetAddresses() : array {
        return [];
    }

    public function getTlsCertificateFile() : ?string {
        return null;
    }

    public function getTotalClientConnectionLimit() : int {
        return 1000;
    }

    public function getClientConnectionLimitPerIpAddress() : int {
        return 10;
    }
}