<?php

namespace Wikibase\TermStore\MediaWiki\Tests\Unit\PackagePrivate;

use PHPUnit\Framework\TestCase;
use Wikibase\TermStore\MediaWiki\PackagePrivate\InMemoryTermIdsAcquirer;

/**
 * @covers \Wikibase\TermStore\MediaWiki\PackagePrivate\InMemoryTermIdsAcquirer
 */
class InMemoryTermIdsAcquirerTest extends TestCase {

	public function testAcquiresUniqueIdsForNonExistingTerms() {
		$termsIdsAcquirer = new InMemoryTermIdsAcquirer();

		$ids = $termsIdsAcquirer->acquireTermIds( [
			'label' => [
				'en' => 'some label',
				'de' => 'another label',
			],
			'alias' => [
				'en' => [ 'alias', 'another alias' ],
				'de' => 'de alias'
			]
		] );

		$this->assertNoDuplicates( $ids );
	}

	public function testReusesIdsOfExistingTerms() {
		$termsIdsAcquirer = new InMemoryTermIdsAcquirer();

		$previouslyAcquiredIds = $termsIdsAcquirer->acquireTermIds( [
			'label' => [
				'en' => 'some label',
				'de' => 'another label',
			],
			'alias' => [
				'en' => [ 'alias', 'another alias' ],
				'de' => 'de alias'
			]
		] );

		$newlyAcquiredIds = $termsIdsAcquirer->acquireTermIds( [
			'label' => [
				'en' => 'some label',
				'de' => 'another label',
			],
			'alias' => [
				'en' => [ 'alias', 'another alias' ],
				'de' => 'de alias'
			]
		] );

		$this->assertEquals(
			$previouslyAcquiredIds,
			$newlyAcquiredIds
		);
	}

	public function testAcquiresAndReusesIdsForNewAndExistingTerms() {
		$termsIdsAcquirer = new InMemoryTermIdsAcquirer();

		$previouslyAcquiredIds = $termsIdsAcquirer->acquireTermIds( [
			'label' => [
				'en' => 'some label',
				'de' => 'another label',
			]
		] );

		$newlyAcquiredIds = $termsIdsAcquirer->acquireTermIds( [
			'label' => [
				'en' => 'some label',
				'de' => 'another label',
			],
			'alias' => [
				'en' => [ 'alias', 'another alias' ],
				'de' => 'de alias'
			]
		] );

		$this->assertNoDuplicates( $newlyAcquiredIds );

		$this->assertEquals(
			$previouslyAcquiredIds,
			array_intersect( $previouslyAcquiredIds, $newlyAcquiredIds )
		);
	}

	private function assertNoDuplicates( $ids ) {
		$this->assertEquals(
			count( $ids ),
			count( array_unique( $ids ) )
		);
	}

	public function testCleanTerms_doesNotReuseIds() {
		$termIdsAcquirer = new InMemoryTermIdsAcquirer();

		$ids1 = $termIdsAcquirer->acquireTermIds( [
			'label' => [
				'en' => 'the label',
				'de' => 'die Bezeichnung',
			],
			'alias' => [
				'en' => [ 'alias', 'another' ],
			],
		] );
		$ids2 = $termIdsAcquirer->acquireTermIds( [
			'label' => [ 'en' => 'the label' ],
			'description' => [ 'en' => 'the description' ],
		] );

		$termIdsAcquirer->cleanTerms( array_merge( $ids1, $ids2 ) );

		$ids3 = $termIdsAcquirer->acquireTermIds( [
			'label' => [ 'en' => 'the label' ],
			'description' => [ 'en' => 'the description' ],
		] );
		$this->assertGreaterThan( max( max( $ids1 ), max( $ids2 ) ), min( $ids3 ) );
	}

	public function testCleanTerms_completelyCleansArray() {
		$termIdsAcquirer = new InMemoryTermIdsAcquirer();

		$ids = $termIdsAcquirer->acquireTermIds( [
			'label' => [
				'en' => 'the label',
				'de' => 'die Bezeichnung',
			],
			'alias' => [
				'en' => [ 'alias', 'another' ],
			],
			'description' => [ 'en' => 'the description' ],
		] );

		$termIdsAcquirer->cleanTerms( $ids );

		$this->assertFalse( $termIdsAcquirer->hasTerms() );
	}

	public function testCleanTerms_keepsOtherIds() {
		$termIdsAcquirer = new InMemoryTermIdsAcquirer();

		$acquiredIds = $termIdsAcquirer->acquireTermIds( [
			'label' => [
				'en' => 'id 1',
				'de' => 'id 2',
			],
			'alias' => [
				'en' => [ 'id 3' ],
			],
			'description' => [ 'en' => 'id 4' ],
		] );

		$termIdsAcquirer->cleanTerms( [ $acquiredIds[1], $acquiredIds[2] ] );

		$this->assertSame(
			[ $acquiredIds[0], $acquiredIds[3] ],
			$termIdsAcquirer->acquireTermIds( [
				'label' => [ 'en' => 'id 1' ],
				'description' => [ 'en' => 'id 4' ],
			] )
		);
	}

}
