<?php

namespace Wikibase\TermStore\MediaWiki\Tests\Unit\MediaWikiDependent;

use PHPUnit\Framework\TestCase;
use Wikibase\TermStore\MediaWiki\TermStoreSchemaUpdater;
use Wikimedia\Rdbms\DatabaseSqlite;

class TermStoreSchemaUpdaterTest extends TestCase {

	public function testUpdaterCreatesTables() {
		$db = DatabaseSqlite::newStandaloneInstance( ':memory:' );
		$dbUpdater = \DatabaseUpdater::newForDB( $db );

		TermStoreSchemaUpdater::onSchemaUpdate( $dbUpdater );

		$dbUpdater->doUpdates( [ 'extensions' ] );

		$this->assertTrue( $db->tableExists( 'wbt_item_terms' ) );
		$this->assertTrue( $db->tableExists( 'wbt_type' ) );
	}

}
