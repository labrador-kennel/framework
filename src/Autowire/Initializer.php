<?php declare(strict_types=1);

namespace Labrador\Http\Autowire;

use Cspray\AnnotatedContainer\Bootstrap\ThirdPartyInitializer;

final class Initializer extends ThirdPartyInitializer {

    public function getPackageName() : string {
        return 'labrador-kennel/http';
    }

    public function getRelativeScanDirectories() : array {
        return ['src'];
    }

    public function getObserverClasses() : array {
        return [
            Observer::class
        ];
    }

    public function getDefinitionProviderClass() : ?string {
        return DefinitionProvider::class;
    }
}