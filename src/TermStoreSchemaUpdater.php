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

	public static function getDdlSqlFilePath() {
		return __DIR__ . '/PackagePrivate/AddNormalizedTermsTablesDDL.sql';
	}

	private function updateSchema() {
		$this->updater->addExtensionTable(
			'wbt_item_terms',
			self::getDdlSqlFilePath()
		);
	}

}
