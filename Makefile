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

# MediaWiki Core related

init_mw:
	rm -rf .mediawiki
	./.travis.install.sh

test_mw: phpunit_mw

phpunit_mw:
	php .mediawiki/tests/phpunit/phpunit.php -c .mediawiki/vendor/wikibase/mediawiki-term-store/phpunit.xml.dist --group MediaWikiCore
