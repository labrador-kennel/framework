<?php

namespace Labrador\Web\Server;

use Amp\Socket\InternetAddress;
use Cspray\AnnotatedContainer\Attribute\Service;

#[Service]
interface HttpServerSettings {

    /**
     * @return list<InternetAddress>
     */
    public function unencryptedInternetAddresses() : array;

    /**
     * @return list<InternetAddress>
     */
    public function encryptedInternetAddresses() : array;

    public function tlsCertificateFile() : ?string;

    /**
     * @return positive-int
     */
    public function totalClientConnectionLimit() : int;

    /**
     * @return positive-int
     */
    public function clientConnectionLimitPerIpAddress() : int;
}
