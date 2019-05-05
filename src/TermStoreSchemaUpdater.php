<?php

namespace Wikibase\TermStore\MediaWiki;

use DatabaseUpdater;

class TermStoreSchemaUpdater {

	private $updater;

	private function __construct( DatabaseUpdater $updater ) {
		$this->updater = $updater;
	}

	public static function onSchemaUpdate( DatabaseUpdater $updater ) {
		( new self( $updater ) )->updateSchema();
	}

	private function updateSchema() {
		if ( !$this->updater->tableExists( 'wbt_item_terms' ) ) {
			$this->createSchema();
		}
	}

	private function createSchema() {
		$this->updater->addExtensionTable(
			'wbt_item_terms',
			__DIR__ . '/PackagePrivate/AddNormalizedTermsTablesDDL.sql'
		);
	}

}