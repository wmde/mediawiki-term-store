<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols -- see evil hack below

namespace Wikibase\TermStore\MediaWiki\Tests\Integration;

use MediaWikiTestCase;
use Wikibase\TermStore\MediaWiki\PackagePrivate\MediaWikiNormalizedTermCleaner;
use Wikibase\TermStore\MediaWiki\TermStoreSchemaUpdater;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\IMaintainableDatabase;

/**
 * @covers \Wikibase\TermStore\MediaWiki\PackagePrivate\MediaWikiNormalizedTermCleaner
 *
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class MediaWikiNormalizedTermCleanerTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();
		$this->tablesUsed[] = 'wbt_type';
		$this->tablesUsed[] = 'wbt_text';
		$this->tablesUsed[] = 'wbt_text_in_lang';
		$this->tablesUsed[] = 'wbt_term_in_lang';
	}

	protected function getSchemaOverrides( IMaintainableDatabase $db ) {
		return [
			'scripts' => [ TermStoreSchemaUpdater::getDdlSqlFilePath() ],
			'create' => [
				'wbt_type',
				'wbt_text',
				'wbt_text_in_lang',
				'wbt_term_in_lang',
			],
		];
	}

	private function getCleaner(): MediaWikiNormalizedTermCleaner {
		$lb = $this->createMock( ILoadBalancer::class );
		$lb->method( 'getConnection' )
			->willReturn( $this->db );
		return new MediaWikiNormalizedTermCleaner( $lb );
	}

	public function testCleanupEverything() {
		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'label' ] );
		$typeId = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'a label' ] );
		$text1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'eine Bezeichnung' ] );
		$text2Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'en', 'wbxl_text_id' => $text1Id ] );
		$textInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'de', 'wbxl_text_id' => $text2Id ] );
		$textInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeId, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeId, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang2Id = $this->db->insertId();

		$this->getCleaner()->cleanTerms( [ $termInLang1Id, $termInLang2Id ] );

		$this->assertSelect( 'wbt_text', 'wbx_id', '*', [] );
		$this->assertSelect( 'wbt_text_in_lang', 'wbxl_id', '*', [] );
		$this->assertSelect( 'wbt_term_in_lang', 'wbtl_id', '*', [] );
		$this->assertSelect( 'wbt_type', 'wby_name', '*', [ [ 'label' ] ] );
	}

	public function testCleanupTermInLangButNoTextInLang() {
		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'label' ] );
		$type1Id = $this->db->insertId();
		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'description' ] );
		$type2Id = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'some text' ] );
		$text1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'etwas Text' ] );
		$text2Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'en', 'wbxl_text_id' => $text1Id ] );
		$textInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'de', 'wbxl_text_id' => $text2Id ] );
		$textInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $type1Id, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $type2Id, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $type1Id, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang3Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $type2Id, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang4Id = $this->db->insertId();

		$this->getCleaner()->cleanTerms( [ $termInLang1Id, $termInLang4Id ] );

		$this->assertSelect(
			'wbt_text',
			'wbx_id',
			'*',
			[ [ $text1Id ], [ $text2Id ] ],
			[ 'ORDER BY' => 'wbx_id' ]
		);
		$this->assertSelect(
			'wbt_text_in_lang',
			'wbxl_id',
			'*',
			[ [ $textInLang1Id ], [ $textInLang2Id ] ],
			[ 'ORDER BY' => 'wbxl_id' ]
		);
		$this->assertSelect(
			'wbt_term_in_lang',
			'wbtl_id',
			'*',
			[ [ $termInLang2Id ], [ $termInLang3Id ] ],
			[ 'ORDER BY' => 'wbtl_id' ]
		);
	}

	public function testCleanupOneTextInLangButNoText() {
		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'label' ] );
		$typeId = $this->db->insertId();
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
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'fr', 'wbxl_text_id' => $text1Id ] );
		$textInLang3Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeId, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeId, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeId, 'wbtl_text_in_lang_id' => $textInLang3Id ] );
		$termInLang3Id = $this->db->insertId();

		$this->getCleaner()->cleanTerms( [ $termInLang1Id ] );

		// $textInLang1Id and $termInLang1Id gone,
		// but $text1Id still there because referenced by $termInLang3Id
		$this->assertSelect(
			'wbt_text',
			'wbx_id',
			'*',
			[ [ $text1Id ], [ $text2Id ] ],
			[ 'ORDER BY' => 'wbx_id' ]
		);
		$this->assertSelect(
			'wbt_text_in_lang',
			'wbxl_id',
			'*',
			[ [ $textInLang2Id ], [ $textInLang3Id ] ],
			[ 'ORDER BY' => 'wbxl_id' ]
		);
		$this->assertSelect(
			'wbt_term_in_lang',
			'wbtl_id',
			'*',
			[ [ $termInLang2Id ], [ $termInLang3Id ] ],
			[ 'ORDER BY' => 'wbtl_id' ]
		);
	}

	public function testCleanupOneText() {
		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'label' ] );
		$typeId = $this->db->insertId();
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
			[ 'wbtl_type_id' => $typeId, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeId, 'wbtl_text_in_lang_id' => $textInLang2Id ] );
		$termInLang2Id = $this->db->insertId();

		$this->getCleaner()->cleanTerms( [ $termInLang1Id ] );

		// $textId1, $textInLang1Id and $termInLang1Id gone
		$this->assertSelect(
			'wbt_text',
			'wbx_id',
			'*',
			[ [ $text2Id ] ]
		);
		$this->assertSelect(
			'wbt_text_in_lang',
			'wbxl_id',
			'*',
			[ [ $textInLang2Id ] ]
		);
		$this->assertSelect(
			'wbt_term_in_lang',
			'wbtl_id',
			'*',
			[ [ $termInLang2Id ] ]
		);
	}

	public function testCleanupLeavesUnrelatedTextsUntouched() {
		$this->db->insert( 'wbt_type',
			[ 'wby_name' => 'label' ] );
		$typeId = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'a label' ] );
		$text1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text',
			[ 'wbx_text' => 'eine Bezeichnung' ] );
		$text2Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'en', 'wbxl_text_id' => $text1Id ] );
		$textInLang1Id = $this->db->insertId();
		$this->db->insert( 'wbt_text_in_lang',
			[ 'wbxl_language' => 'de', 'wbxl_text_id' => $text2Id ] );
		$textInLang2Id = $this->db->insertId();
		$this->db->insert( 'wbt_term_in_lang',
			[ 'wbtl_type_id' => $typeId, 'wbtl_text_in_lang_id' => $textInLang1Id ] );
		$termInLang1Id = $this->db->insertId();

		$this->getCleaner()->cleanTerms( [ $termInLang1Id ] );

		// $text2Id and $textInLang2Id are not used by any term_in_lang,
		// but we should not attempt to clean them up
		$this->assertSelect(
			'wbt_text',
			'wbx_id',
			'*',
			[ [ $text2Id ] ]
		);
		$this->assertSelect(
			'wbt_text_in_lang',
			'wbxl_id',
			'*',
			[ [ $textInLang2Id ] ]
		);
	}

}
