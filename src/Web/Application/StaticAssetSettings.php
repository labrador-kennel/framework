<?php declare(strict_types=1);

namespace Labrador\Web\Application;

final class StaticAssetSettings {

    public function __construct(
        public readonly string $assetDir,
        public readonly string $pathPrefix
    ) {
    }
}
