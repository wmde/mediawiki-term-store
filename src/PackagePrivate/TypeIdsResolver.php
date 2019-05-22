<?php

namespace Wikibase\TermStore\MediaWiki\PackagePrivate;

/**
 * A service to turn type IDs into types,
 * the inverse of {@link TypeIdsAcquirer}.
 */
interface TypeIdsResolver {

	/**
	 * Resolves types for the given type IDs.
	 *
	 * @param int[] $typeIds
	 * @return string[] Array from type IDs to type names. Unknown IDs in $typeIds are omitted.
	 */
	public function resolveTypeIds( array $typeIds ): array;

}
