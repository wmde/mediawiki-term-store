.PHONY: ci test phpunit cs stan covers

DEFAULT_GOAL := check

check: cs test

test: phpunit

cs: phpcs

phpunit:
	./vendor/bin/phpunit  --exclude-group MediaWikiCore

phpcs:
	./vendor/bin/phpcs -p -s

covers:
	./vendor/bin/covers-validator

