.PHONY: test phpunit cs covers init_mw test_mw phpunit_mw ci

DEFAULT_GOAL := check

check: cs test

test: phpunit

cs: phpcs

phpunit:
	./vendor/bin/phpunit

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
	php .mediawiki/tests/phpunit/phpunit.php -c .mediawiki/vendor/wikibase/mediawiki-term-store/phpunit.xml.dist ./tests/Unit/MediaWikiDependent
	php .mediawiki/tests/phpunit/phpunit.php -c .mediawiki/vendor/wikibase/mediawiki-term-store/phpunit.xml.dist ./tests/Integration

ci: check test_mw
