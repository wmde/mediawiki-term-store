<?php

namespace Wikibase\TermStore\MediaWiki\Tests\Unit\PackagePrivate;

use PHPUnit\Framework\TestCase;
use Wikibase\TermStore\MediaWiki\PackagePrivate\InMemoryTermIdsStore;

/**
 * @covers \Wikibase\TermStore\MediaWiki\PackagePrivate\InMemoryTermIdsStore
 */
class InMemoryTermIdsStoreTest extends TestCase {

	public function testAcquiresUniqueIdsForNonExistingTerms() {
		$termsIdsAcquirer = new InMemoryTermIdsStore();

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
		$termsIdsAcquirer = new InMemoryTermIdsStore();

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
		$termsIdsAcquirer = new InMemoryTermIdsStore();

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

	public function testResolveTermIds_returnsAcquiredTerms_butNotAllTerms() {
		$termIdsStore = new InMemoryTermIdsStore();

		$termIds1 = $termIdsStore->acquireTermIds( [
			'label' => [
				'en' => 'some label',
				'de' => 'eine Beschriftung',
			],
		] );
		$termIds2 = $termIdsStore->acquireTermIds( [
			'label' => [
				'de' => 'eine Beschriftung',
			],
			'alias' => [
				'de' => [ 'ein Alias', 'noch ein Alias' ],
			],
		] );

		$terms1 = $termIdsStore->resolveTermIds( $termIds1 );
		$terms2 = $termIdsStore->resolveTermIds( $termIds2 );

		$this->assertSame( [
			'label' => [
				'en' => [ 'some label' ],
				'de' => [ 'eine Beschriftung' ],
			],
		], $terms1 );
		$this->assertSame( [
			'label' => [
				'de' => [ 'eine Beschriftung' ],
			],
			'alias' => [
				'de' => [ 'ein Alias', 'noch ein Alias' ],
			],
		], $terms2 );
	}

	public function testCleanTerms_doesNotReuseIds() {
		$termIdsStore = new InMemoryTermIdsStore();

		$originalIds = $termIdsStore->acquireTermIds( [
			'label' => [
				'en' => 'the label',
				'de' => 'die Bezeichnung',
			],
			'alias' => [
				'en' => [ 'alias', 'another' ],
			],
			'description' => [ 'en' => 'the description' ],
		] );

		$termIdsStore->cleanTerms( $originalIds );

		$newIds = $termIdsStore->acquireTermIds( [
			'label' => [ 'en' => 'the label' ],
			'description' => [ 'en' => 'the description' ],
		] );

		// Assert that the lowest new id is higher than the highest original id
		$this->assertGreaterThan( max( $originalIds ), min( $newIds ) );
	}

	public function testCleanTerms_keepsOtherIds() {
		$termIdsStore = new InMemoryTermIdsStore();

		$acquiredIds = $termIdsStore->acquireTermIds( [
			'label' => [
				'en' => 'id 1',
				'de' => 'id 2',
			],
			'alias' => [
				'en' => [ 'id 3' ],
			],
			'description' => [ 'en' => 'id 4' ],
		] );

		$termIdsStore->cleanTerms( [ $acquiredIds[1], $acquiredIds[2] ] );

		$this->assertSame(
			[ $acquiredIds[0], $acquiredIds[3] ],
			$termIdsStore->acquireTermIds( [
				'label' => [ 'en' => 'id 1' ],
				'description' => [ 'en' => 'id 4' ],
			] )
		);
	}

}
