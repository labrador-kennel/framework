<?php declare(strict_types=1);

namespace Labrador\Web\Autowire;

use Cspray\AnnotatedContainer\Bootstrap\ThirdPartyInitializer;

final class Initializer extends ThirdPartyInitializer {

    public function packageName() : string {
        return 'labrador-kennel/framework';
    }

    public function relativeScanDirectories() : array {
        return ['src'];
    }

    public function definitionProviderClass() : ?string {
        return DefinitionProvider::class;
    }
}
