<?php declare(strict_types=1);

namespace Labrador\Security;

use Adbar\Dot;
use Cspray\AnnotatedContainer\ContainerFactory\ParameterStore;
use Cspray\Typiphy\Type;
use Cspray\Typiphy\TypeIntersect;
use Cspray\Typiphy\TypeUnion;

final class SecretsParameterStore implements ParameterStore {

    private readonly Dot $data;

    public function __construct(SecretsSource... $secretsSource) {
        $data = [];
        foreach ($secretsSource as $source) {
            $data[$source->getName()] = $source->getData();
        }
        $this->data = dot($data);
    }

    public function getName() : string {
        return 'secrets';
    }

    public function fetch(TypeUnion|Type|TypeIntersect $type, string $key) : mixed {
        return $this->data->get($key);
    }
}