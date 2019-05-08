<?php

namespace Wikibase\TermStore\MediaWiki\Tests\Unit\PackagePrivate;

use PHPUnit\Framework\TestCase;
use Wikibase\TermStore\MediaWiki\PackagePrivate\InMemoryTermIdsAcquirer;

class InMemeoryTermIdsAcquirerTest extends TestCase {

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

}
