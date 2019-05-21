<?php

namespace Wikibase\TermStore\MediaWiki\PackagePrivate;

/**
 * Acquires unique and constant ids of types, stored in memory.
 */
class InMemoryTypeIdsAcquirer implements TypeIdsAcquirer {
	private $types = [];
	private $lastId = 0;

	public function acquireTypeIds( $types ) {
		$ids = [];
		foreach ( $types as $type ) {
			if ( !isset( $this->types[$type] ) ) {
				$this->types[$type] = ++$this->lastId;
			}
			$ids[$type] = $this->types[$type];
		}

		return $ids;
	}
}
