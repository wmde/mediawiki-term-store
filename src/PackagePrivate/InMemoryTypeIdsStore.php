<?php

namespace Wikibase\TermStore\MediaWiki\PackagePrivate;

/**
 * Acquires and resolves unique and constant ids of types, stored in memory.
 */
class InMemoryTypeIdsStore implements TypeIdsAcquirer, TypeIdsResolver {
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

	public function resolveTypeIds( array $typeIds ): array {
		$types = [];
		foreach ( $this->types as $typeName => $typeId ) {
			if ( in_array( $typeId, $typeIds ) ) {
				$types[$typeId] = $typeName;
			}
		}
		return $types;
	}
}
