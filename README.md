# Wikibase MediaWiki TermStore

[![Build Status](https://travis-ci.org/wmde/mediawiki-term-store.svg?branch=master)](https://travis-ci.org/wmde/mediawiki-term-store)
[![Latest Stable Version](https://poser.pugx.org/wikibase/mediawiki-term-store/version.png)](https://packagist.org/packages/wikibase/mediawiki-term-store)
[![Download count](https://poser.pugx.org/wikibase/mediawiki-term-store/d/total.png)](https://packagist.org/packages/wikibase/mediawiki-term-store)

MediaWiki based implementation of [Wikibase TermStore](https://github.com/wmde/wikibase-term-store). 

## Usage

TODO

## Installation

To use the Wikibase TermStore library in your project, simply add a dependency on wikibase/mediawiki-term-store
to your project's `composer.json` file. Here is a minimal example of a `composer.json`
file that just defines a dependency on wikibase/mediawiki-term-store 1.x:

```json
{
    "require": {
        "wikibase/mediawiki-term-store": "~1.0"
    }
}
```

## Development

Start by installing the project dependencies by executing

    composer update

You can run the style checks by executing

    make cs
    
Since the library depends on MediaWiki, you need to have a working MediaWiki
installation to run the tests. You need these two steps to run the tests:

* Load `vendor/autoload.php` of this library in your MediaWiki's `LocalSettings.php` file
* Execute `maintenance/phpunit.php -c /path/to/this/lib/phpunit.xml.dist`

For an example see the TravisCI setup (`.travis.yml` and `.travis.install.sh`)
