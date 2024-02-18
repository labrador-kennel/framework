#!/usr/bin/env just --justfile

_default:
    just --list --unsorted

ci: test static-analysis

install-deps:
    composer install

update-deps:
    composer update

static-analysis:
    vendor/bin/psalm.phar

test:
    vendor/bin/phpunit