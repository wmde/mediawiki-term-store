<?php

namespace Wikibase\TermStore\MediaWiki\Tests\Integration\PackagePrivate;

use PHPUnit\Framework\TestCase;
use Wikibase\TermStore\MediaWiki\PackagePrivate\DatabaseTermIdsResolver;
use Wikibase\TermStore\MediaWiki\PackagePrivate\StaticTypeIdsStore;
use Wikibase\TermStore\MediaWiki\PackagePrivate\TypeIdsResolver;
use Wikibase\TermStore\MediaWiki\TermStoreSchemaUpdater;
use Wikibase\TermStore\MediaWiki\Tests\Util\FakeLoadBalancer;
use Wikimedia\Rdbms\Database;
use Wikimedia\Rdbms\DatabaseSqlite;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @covers \Wikibase\TermStore\MediaWiki\PackagePrivate\DatabaseTermIdsResolver
 */
class DatabaseTermIdsResolverTest extends TestCase {

	const TYPE_LABEL = 1;
	const TYPE_DESCRIPTION = 2;
	const TYPE_ALIAS = 3;

	/** @var TypeIdsResolver */
	private $typeIdsResolver;

	/** @var IDatabase */
	private $db;

	public function setUp() {
		$this->typeIdsResolver = new StaticTypeIdsStore( [
			'label' => self::TYPE_LABEL,
			'description' => self::TYPE_DESCRIPTION,
			'alias' => self::TYPE_ALIAS,
		] );
		$this->db = DatabaseSqlite::newStandaloneInstance( ':memory:' );
		$this->db->sourceFile( TermStoreSchemaUpdater::getSqlFileAbsolutePath() );
	}

	public function testCanResolveEverything() {
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'text' ] );
		$text1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'Text' ] );
		$text2Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'en', 'wbxl_text_id' => $text1Id ] );
		$textInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'de', 'wbxl_text_id' => $text2Id ] );
		$textInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang3Id = $this->db->insertId();

		$resolver = new DatabaseTermIdsResolver(
			$this->typeIdsResolver,
			new FakeLoadBalancer( [
				'dbr' => $this->db,
			] )
		);
		$terms = $resolver->resolveTermIds( [ $termInLang1Id, $termInLang2Id, $termInLang3Id ] );

		$this->assertSame( [
			'label' => [
				'en' => [ 'text' ],
				'de' => [ 'Text' ],
			],
			'description' => [
				'en' => [ 'text' ],
			],
		], $terms );
	}

	public function testReadsEverythingFromReplicaIfPossible() {
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'text' ] );
		$text1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'Text' ] );
		$text2Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'en', 'wbxl_text_id' => $text1Id ] );
		$textInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'de', 'wbxl_text_id' => $text2Id ] );
		$textInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang3Id = $this->db->insertId();

		$dbw = $this->createMock( Database::class );
		$dbw->expects( $this->never() )->method( 'query' );

		$resolver = new DatabaseTermIdsResolver(
			$this->typeIdsResolver,
			new FakeLoadBalancer( [
				'dbr' => $this->db,
				'dbw' => $dbw,
			] )
		);
		$resolver->resolveTermIds( [ $termInLang1Id, $termInLang2Id, $termInLang3Id ] );
	}

	public function testFallsBackToMasterIfNecessaryAndAllowed() {
		$dbr = $this->db;
		$dbw = DatabaseSqlite::newStandaloneInstance( ':memory:' );
		$dbw->sourceFile( TermStoreSchemaUpdater::getSqlFileAbsolutePath() );
		// both master and replica have most of the data
		foreach ( [ $dbr, $dbw ] as $db ) {
			// note: we assume that both DBs get the same insert IDs
			$db->insert( 'wbt_text',
				[ 'wbx_text' => 'text' ] );
			$text1Id = $db->insertId();
			$db->insert( 'wbt_text',
				[ 'wbx_text' => 'Text' ] );
			$text2Id = $db->insertId();
			$db->insert( 'wbt_text_in_lang',
				[ 'wbxl_language' => 'en', 'wbxl_text_id' => $text1Id ] );
			$textInLang1Id = $db->insertId();
			$db->insert( 'wbt_text_in_lang',
				[ 'wbxl_language' => 'de', 'wbxl_text_id' => $text2Id ] );
			$textInLang2Id = $db->insertId();
			$db->insert( 'wbt_term_in_lang',
				[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
			$termInLang1Id = $db->insertId();
			$db->insert( 'wbt_term_in_lang',
				[ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
			$termInLang2Id = $db->insertId();
		}
		// only master has the last term_in_lang row
		$db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang3Id = $db->insertId();

		$resolver = new DatabaseTermIdsResolver(
			$this->typeIdsResolver,
			new FakeLoadBalancer( [
				'dbr' => $dbr,
				'dbw' => $dbw,
			] ),
			true
		);
		$terms = $resolver->resolveTermIds( [ $termInLang1Id, $termInLang2Id, $termInLang3Id ] );

		$this->assertSame( [
			'label' => [
				'en' => [ 'text' ],
				'de' => [ 'Text' ],
			],
			'description' => [
				'en' => [ 'text' ],
			],
		], $terms );
	}

	public function testDoesNotFallBackToMasterIfNotAllowed() {
		$dbr = $this->db;
		$dbw = DatabaseSqlite::newStandaloneInstance( ':memory:' );
		$dbw->sourceFile( TermStoreSchemaUpdater::getSqlFileAbsolutePath() );
		// both master and replica have most of the data
		foreach ( [ $dbr, $dbw ] as $db ) {
			// note: we assume that both DBs get the same insert IDs
			$db->insert( 'wbt_text',
				[ 'wbx_text' => 'text' ] );
			$text1Id = $db->insertId();
			$db->insert( 'wbt_text',
				[ 'wbx_text' => 'Text' ] );
			$text2Id = $db->insertId();
			$db->insert( 'wbt_text_in_lang',
				[ 'wbxl_language' => 'en', 'wbxl_text_id' => $text1Id ] );
			$textInLang1Id = $db->insertId();
			$db->insert( 'wbt_text_in_lang',
				[ 'wbxl_language' => 'de', 'wbxl_text_id' => $text2Id ] );
			$textInLang2Id = $db->insertId();
			$db->insert( 'wbt_term_in_lang',
				[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
			$termInLang1Id = $db->insertId();
			$db->insert( 'wbt_term_in_lang',
				[ 'wbtl_type_id' => self::TYPE_DESCRIPTION, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
			$termInLang2Id = $db->insertId();
		}
		// only master has the last term_in_lang row
		$db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => self::TYPE_LABEL, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang3Id = $db->insertId();

		$resolver = new DatabaseTermIdsResolver(
			$this->typeIdsResolver,
			new FakeLoadBalancer( [
				'dbr' => $dbr,
				'dbw' => $dbw,
			] ),
			false
		);
		$terms = $resolver->resolveTermIds( [ $termInLang1Id, $termInLang2Id, $termInLang3Id ] );

		$this->assertSame( [
			'label' => [
				'en' => [ 'text' ],
				// 'de' => [ 'Text' ], // this is the row missing from the replica
			],
			'description' => [
				'en' => [ 'text' ],
			],
		], $terms );
	}

}
