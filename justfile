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

code-lint:
    @vendor/bin/phpcs --version
    @vendor/bin/phpcs -p --colors --standard=./vendor/cspray/labrador-coding-standard/ruleset.xml --exclude=Generic.Files.LineLength src test

code-lint-fix:
    @vendor/bin/phpcbf -p --standard=./vendor/cspray/labrador-coding-standard/ruleset.xml --exclude=Generic.Files.LineLength src test

test:
    vendor/bin/phpunit

# Run all CI checks. ALL checks will run, regardless of failures
ci-check:
    -@just test
    -@just static-analysis
    @echo ""
    -@just code-lint
