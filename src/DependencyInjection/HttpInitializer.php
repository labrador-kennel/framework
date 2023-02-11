<?php declare(strict_types=1);

namespace Labrador\Http\DependencyInjection;
use Cspray\AnnotatedContainer\Bootstrap\ThirdPartyInitializer;

final class HttpInitializer extends ThirdPartyInitializer {

    public function getPackageName() : string {
        return 'labrador-kennel/http';
    }

    public function getRelativeScanDirectories() : array {
        return ['src'];
    }

    public function getObserverClasses() : array {
        return [
            AutowireObserver::class
        ];
    }

    public function getDefinitionProviderClass() : ?string {
        return ThirdPartyDefinitionProvider::class;
    }
}