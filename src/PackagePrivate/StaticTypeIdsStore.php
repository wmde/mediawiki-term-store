<?php

namespace Wikibase\TermStore\MediaWiki\PackagePrivate;

use DomainException;

/**
 * A type IDs acquirer and resolver that only encapsulates access to a static array of IDs.
 */
class StaticTypeIdsStore implements TypeIdsAcquirer, TypeIdsResolver {

	/** @var int[] */
	private $typeIdsByName;

	/** @var string[] */
	private $typeNamesById;

	/**
	 * @param int[] $types Array from type name to type ID.
	 */
	public function __construct( array $types ) {
		$this->typeIdsByName = $types;
		$this->typeNamesById = array_flip( $types );
	}

	public function acquireTypeIds( array $types ): array {
		$ret = [];
		foreach ( $types as $typeName ) {
			if ( array_key_exists( $typeName, $this->typeIdsByName ) ) {
				$ret[$typeName] = $this->typeIdsByName[$typeName];
			} else {
				throw new DomainException( 'Unknown type ' . $typeName . ' not supported!' );
			}
		}
		return $ret;
	}

	public function resolveTypeIds( array $typeIds ): array {
		$ret = [];
		foreach ( $typeIds as $typeId ) {
			if ( array_key_exists( $typeId, $this->typeNamesById ) ) {
				$ret[$typeId] = $this->typeNamesById[$typeId];
			}
		}
		return $ret;
	}

}
