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

To get IDE autocompletion for MediaWiki, you can place a copy of MediaWiki inside the gitignored `mediawiki` directory.

    git clone https://github.com/wikimedia/mediawiki.git

You can run the style checks by executing

    make cd

## Testing

Unit tests that do not depend on mediawiki core can be simply run with

	make test

Unit tests that depend on mediawiki core must be in group 'MediaWikiCore' and can be run in the following way:

	// execute once or only when you want to update mediawiki core version this library use to execute tests
	MW=1.32.0 DBTYPE=sqlite make init_mw

	// then to run unit tests, you can now execute
	make test_mw
