<?php

namespace Wikibase\TermStore\MediaWiki;

use DatabaseUpdater;

class TermStoreSchemaUpdater {

	private $updater;

	public static function getSqlFileAbsolutePath() {
		return __DIR__ . '/PackagePrivate/AddNormalizedTermsTablesDDL.sql';
	}

	private function __construct( DatabaseUpdater $updater ) {
		$this->updater = $updater;
	}

	public static function onSchemaUpdate( DatabaseUpdater $updater ) {
		( new self( $updater ) )->updateSchema();
	}

	private function updateSchema() {
		$this->updater->addExtensionTable(
			'wbt_item_terms',
			self::getSqlFileAbsolutePath()
		);
	}

}
