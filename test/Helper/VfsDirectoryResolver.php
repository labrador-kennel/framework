<?php

namespace Labrador\Test\Helper;

use Cspray\AnnotatedContainer\Bootstrap\BootstrappingDirectoryResolver;

class VfsDirectoryResolver implements BootstrappingDirectoryResolver {

    private readonly string $realRoot;
    private readonly string $virtualRoot;

    public function __construct() {
        $this->realRoot = dirname(__DIR__, 2);
        $this->virtualRoot = 'vfs://root';
    }

    public function getConfigurationPath(string $subPath) : string {
        return sprintf('%s/%s', $this->virtualRoot, $subPath);
    }

    public function getCachePath(string $subPath) : string {
        return sprintf('%s/%s', $this->virtualRoot, $subPath);
    }

    public function getLogPath(string $subPath) : string {
        return sprintf('%s/%s', $this->virtualRoot, $subPath);
    }

    public function getPathFromRoot(string $subPath) : string {
        return sprintf('%s/%s', $this->realRoot, $subPath);
    }

    public function getVendorPath() : string {
        return sprintf('%s/vendor', $this->virtualRoot);
    }
}