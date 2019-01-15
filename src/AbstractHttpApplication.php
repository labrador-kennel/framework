<?php declare(strict_types=1);

namespace Cspray\Labrador\Http;

use Amp\Promise;
use Amp\Socket\Server;
use Cspray\Labrador\Http\Plugin\RouterPlugin;
use Cspray\Labrador\StandardApplication;
use function Amp\call;

abstract class AbstractHttpApplication extends StandardApplication implements RouterPlugin {

    /**
     * Perform whatever logic or operations your application requires; return a Promise that resolves when you app is
     * finished running.
     *
     * This method should avoid throwing an exception and instead fail the Promise with the Exception that caused the
     * application to crash.
     *
     * @return Promise
     */
    public function execute() : Promise {
        return call(function() {

        });
    }

    /**
     * @return Server[]
     */
    abstract protected function getSocketServers() : array;
}