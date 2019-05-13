<?php

namespace Wikibase\TermStore\MediaWiki\PackagePrivate;

use Wikibase\DataModel\Entity\EntityId;

/**
 * in-memory, @inheritDoc
 */
class InMemoryEntityTermStore implements EntityTermStore {
	private $entityTerms = [];

	public function setTerms( EntityId $entityId, array $termsArray ) {
		$this->entityTerms[$entityId->getSerialization()] = $termsArray;
	}

	public function unsetTerms( EntityId $entityId ) {
		unset( $this->entityTerms[$entityId->getSerialization()] );
	}

	/**
	 * check that terms for the given entity id has the given terms stored
	 *
	 * @param EntityId $entityId
	 * @param array $termsArray same as $termsArray for InMemoryEntityTermStore::setTerms()
	 *
	 * @return bool
	 */
	public function hasExactTerms( EntityId $entityId, array $termsArray ) {
		if ( !isset( $this->entityTerms[$entityId->getSerialization()] ) ) {
			return false;
		}

		return $termsArray === $this->entityTerms[$entityId->getSerialization()];
	}

	/**
	 * Check that given entity id has no terms stored
	 *
	 * @return bool
	 */
	public function hasNoTerms( EntityId $entityId ) {
		return !isset( $this->entityTerms[$entityId->getSerialization()] );
	}

}
