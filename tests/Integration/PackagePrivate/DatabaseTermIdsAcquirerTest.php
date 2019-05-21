<?php

namespace Wikibase\TermStore\MediaWiki\Tests\Unit\PackagePrivate;

use PHPUnit\Framework\TestCase;
use Wikibase\TermStore\MediaWiki\TermStoreSchemaUpdater;
use Wikibase\TermStore\MediaWiki\PackagePrivate\DatabaseTermIdsAcquirer;
use Wikibase\TermStore\MediaWiki\PackagePrivate\InMemoryTypeIdsAcquirer;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\DatabaseSqlite;

class DatabaseTermIdsAcquirerTest extends TestCase {

	/**
	 * @var IDatabase $db
	 */
	private $db;

	public function setUp() {
		$this->db = DatabaseSqlite::newStandaloneInstance( ':memory:' );
		$this->db->sourceFile( TermStoreSchemaUpdater::getSqlFileAbsolutePath() );
	}

	public function testAcquireTermIdsReturnsArrayOfIdsForAllTerms() {
		$typeIdsAcquirer = new InMemoryTypeIdsAcquirer();

		$dbTermIdsAcquirer = new DatabaseTermIdsAcquirer(
			$this->db,
			$this->db,
			$typeIdsAcquirer
		);

		$termsArray = [
			'label' => [
				'en' => 'same',
				'de' => 'same',
			],
			'description' => [ 'en' => 'same' ],
			'alias' => [
				'en' => [ 'same', 'same', 'another', 'yet another' ]
			]
		];

		$acquiredTermIds = $dbTermIdsAcquirer->acquireTermIds( $termsArray );

		$this->assertInternalType( 'array', $acquiredTermIds );
		$this->assertCount( 7, $acquiredTermIds );
	}

	public function testAcquireTermIdsStoresTermsInDatabase() {
		$typeIdsAcquirer = new InMemoryTypeIdsAcquirer();
		$alreadyAcquiredTypeIds = $typeIdsAcquirer->acquireTypeIds(
			[ 'label', 'description', 'alias' ]
		);

		$dbTermIdsAcquirer = new DatabaseTermIdsAcquirer(
			$this->db,
			$this->db,
			$typeIdsAcquirer
		);

		$termsArray = [
			'label' => [
				'en' => 'same',
				'de' => 'same',
			],
			'description' => [ 'en' => 'same' ],
			'alias' => [
				'en' => [ 'same', 'same', 'another', 'yet another' ]
			]
		];

		$acquiredTermIds = $dbTermIdsAcquirer->acquireTermIds( $termsArray );

		$this->assertTermsArrayExistInDb( $termsArray, $alreadyAcquiredTypeIds );
	}

	public function testAcquireTermIdsStoresOnlyUniqueTexts() {
		$typeIdsAcquirer = new InMemoryTypeIdsAcquirer();

		$dbTermIdsAcquirer = new DatabaseTermIdsAcquirer(
			$this->db,
			$this->db,
			$typeIdsAcquirer
		);

		$termsArray = [
			'label' => [
				'en' => 'same',
				'de' => 'same',
			],
			'description' => [ 'en' => 'same' ],
			'alias' => [
				'en' => [ 'same', 'same', 'another', 'yet another' ]
			]
		];

		$acquiredTermIds = $dbTermIdsAcquirer->acquireTermIds( $termsArray );

		$this->assertSame(
			3,
			$this->db->selectRowCount( 'wbt_text', '*' )
		);
	}

	public function testAcquireTermIdsStoresOnlyUniqueTextInLang() {
		$typeIdsAcquirer = new InMemoryTypeIdsAcquirer();

		$dbTermIdsAcquirer = new DatabaseTermIdsAcquirer(
			$this->db,
			$this->db,
			$typeIdsAcquirer
		);

		$termsArray = [
			'label' => [
				'en' => 'same',
				'de' => 'same',
			],
			'description' => [ 'en' => 'same' ],
			'alias' => [
				'en' => [ 'same', 'same', 'another', 'yet another' ]
			]
		];

		$acquiredTermIds = $dbTermIdsAcquirer->acquireTermIds( $termsArray );

		$this->assertSame(
			4,
			$this->db->selectRowCount( 'wbt_text_in_lang', '*' )
		);
	}

	public function testAcquireTermIdsStoresOnlyUniqueTermInLang() {
		$typeIdsAcquirer = new InMemoryTypeIdsAcquirer();

		$dbTermIdsAcquirer = new DatabaseTermIdsAcquirer(
			$this->db,
			$this->db,
			$typeIdsAcquirer
		);

		$termsArray = [
			'label' => [
				'en' => 'same',
				'de' => 'same',
			],
			'description' => [ 'en' => 'same' ],
			'alias' => [
				'en' => [ 'same', 'same', 'another', 'yet another' ]
			]
		];

		$acquiredTermIds = $dbTermIdsAcquirer->acquireTermIds( $termsArray );

		$this->assertSame(
			6,
			$this->db->selectRowCount( 'wbt_term_in_lang', '*' )
		);
	}

	public function testAcquireTermIdsReusesExistingTerms() {
		$termsArray = [
			'label' => [
				'en' => 'same',
				'de' => 'same',
			],
			'description' => [ 'en' => 'same' ],
			'alias' => [
				'en' => [ 'same', 'same', 'another', 'yet another' ]
			]
		];

		// We will populate DB with two terms that both have
		// text "same". One is of type "label" in language "en",
		// and the other is of type "alias" in language "en.
		//
		// TermIdsAcquirer should then reuse those terms for the given
		// termsArray above, meaning thoese pre-inserted terms will
		// appear (their ids) in the returned array from
		// TermIdsAcquirer::acquireTermIds( $termsArray )
		$typeIdsAcquirer = new InMemoryTypeIdsAcquirer();
		$alreadyAcquiredTypeIds = $typeIdsAcquirer->acquireTypeIds(
			[ 'label', 'description', 'alias' ]
		);

		$this->db->insert( 'wbt_text', [ 'wbx_text' => 'same' ] );
		$sameTextId = $this->db->insertId();

		$this->db->insert(
			'wbt_text_in_lang',
			[ 'wbxl_text_id' => $sameTextId, 'wbxl_language' => 'en' ]
		);
		$enSameTextInLangId = $this->db->insertId();

		$this->db->insert(
			'wbt_term_in_lang',
			[ 'wbtl_text_in_lang_id' => $enSameTextInLangId,
			  'wbtl_type_id' => $alreadyAcquiredTypeIds['label'] ]
		);
		$labelEnSameTermInLangId = (string)$this->db->insertId();

		$this->db->insert(
			'wbt_term_in_lang',
			[ 'wbtl_text_in_lang_id' => $enSameTextInLangId,
			  'wbtl_type_id' => $alreadyAcquiredTypeIds['alias'] ]
		);
		$aliasEnSameTermInLangId = (string)$this->db->insertId();

		$dbTermIdsAcquirer = new DatabaseTermIdsAcquirer(
			$this->db,
			$this->db,
			$typeIdsAcquirer
		);

		$acquiredTermIds = $dbTermIdsAcquirer->acquireTermIds( $termsArray );

		$this->assertCount( 7, $acquiredTermIds );

		// We will assert that the returned ids of acquired terms contains
		// one occurence of the term id for en label "same" that already existed in db,
		// and two occurences of the term id for en alias "same" that already existed
		// in db.
		$this->assertCount(
			1,
			array_filter(
				$acquiredTermIds,
				function ( $id ) use ( $labelEnSameTermInLangId ) {
					return $id === $labelEnSameTermInLangId;
				}
			)
		);
		$this->assertCount(
			2,
			array_filter(
				$acquiredTermIds,
				function ( $id ) use ( $aliasEnSameTermInLangId ) {
					return $id === $aliasEnSameTermInLangId;
				}
			)
		);
	}

	private function assertTermsArrayExistInDb( $termsArray, $typeIds ) {
		foreach ( $termsArray as $type => $textsPerLang ) {
			foreach ( $textsPerLang as $lang => $texts ) {
				foreach ( (array)$texts as $text ) {
					$textId = $this->db->selectField(
						'wbt_text',
						'wbx_id',
						[ 'wbx_text' => $text ]
					);

					$this->assertNotEmpty(
						$textId,
						"Expected record for text '$text' is not in wbt_text"
					);

					$textInLangId = $this->db->selectField(
						'wbt_text_in_lang',
						'wbxl_id',
						[ 'wbxl_language' => $lang, 'wbxl_text_id' => $textId ]
					);

					$this->assertNotEmpty(
						$textInLangId,
						"Expected text '$text' in language '$lang' is not in wbt_text_in_lang"
					);

					$this->assertNotEmpty(
						$this->db->selectField(
							'wbt_term_in_lang',
							'wbtl_id',
							[ 'wbtl_type_id' => $typeIds[$type], 'wbtl_text_in_lang_id' => $textInLangId ]
						),
						"Expected $type '$text' in language '$lang' is not in wbt_term_in_lang"
					);
				}
			}
		}
	}
}
