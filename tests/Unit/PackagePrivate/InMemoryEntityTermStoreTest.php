<?php

namespace Wikibase\TermStore\MediaWiki\Tests\Unit\PackagePrivate;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\TermStore\MediaWiki\PackagePrivate\InMemoryEntityTermStore;

class InMemoryEntityTermStoreTest extends TestCase {

	public function testHasTermsReturnsTrueWhenTermsHaveBeenSet() {
		list( $entityId, $termsArray ) = $this->getEntityIdAndTermsArray();
		$termStore = new InMemoryEntityTermStore();

		$termStore->setTerms( $entityId, $termsArray );

		$this->assertTrue(
			$termStore->hasExactTerms( $entityId, $termsArray )
		);
	}

	public function testHasTermsReturnsFalseWhenTermsNeverBeenSet() {
		list( $entityId, $termsArray ) = $this->getEntityIdAndTermsArray();
		$termStore = new InMemoryEntityTermStore();

		$this->assertFalse(
			$termStore->hasExactTerms( $entityId, $termsArray )
		);
	}

	public function testHasTermsReturnsFalseWhenTermsHaveBeenUnset() {
		list( $entityId, $termsArray ) = $this->getEntityIdAndTermsArray();
		$termStore = new InMemoryEntityTermStore();

		$termStore->setTerms( $entityId, $termsArray );
		$termStore->unsetTerms( $entityId );

		$this->assertFalse(
			$termStore->hasExactTerms( $entityId, $termsArray )
		);
	}

	public function testHasNoTermsReturnsFalseWhenTermsHaveBeenSet() {
		list( $entityId, $termsArray ) = $this->getEntityIdAndTermsArray();
		$termStore = new InMemoryEntityTermStore();

		$termStore->setTerms( $entityId, $termsArray );

		$this->assertFalse(
			$termStore->hasNoTerms( $entityId )
		);
	}

	public function testHasNoTermsReturnsTrueWhenTermsNeverBeenSet() {
		list( $entityId, $termsArray ) = $this->getEntityIdAndTermsArray();
		$termStore = new InMemoryEntityTermStore();

		$this->assertTrue(
			$termStore->hasNoTerms( $entityId )
		);
	}

	public function testHasNoTermsReturnsTrueWhenTermsHaveBeenUnset() {
		list( $entityId, $termsArray ) = $this->getEntityIdAndTermsArray();
		$termStore = new InMemoryEntityTermStore();

		$termStore->setTerms( $entityId, $termsArray );
		$termStore->unsetTerms( $entityId );

		$this->assertTrue(
			$termStore->hasNoTerms( $entityId )
		);
	}

	private function getEntityIdAndTermsArray(): array {
		return [
			new ItemId( 'Q123' ),
			[
				'label' => [
					'en' => 'some label',
					'de' => 'another label',
				],
				'alias' => [
					'en' => [ 'alias', 'another alias' ],
					'de' => 'de alias'
				]
			]
		];
	}
}
