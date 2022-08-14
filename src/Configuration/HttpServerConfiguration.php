<?php

namespace Cspray\Labrador\Http\Configuration;

use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\Labrador\Http\Http\InternetProtocolAddress;

#[Service]
interface HttpServerConfiguration {

    /**
     * @return list<InternetProtocolAddress>
     */
    public function getInternetProtocolAddresses() : array;

    public function getTlsCertificatePath() : ?string;

}