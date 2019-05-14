<?php

namespace Wikibase\TermStore\MediaWiki\PackagePrivate;

/**
 * Interface for deleting IDs acquired from a {@link TermIdsAcquirer},
 * including any further cleanup if necessary.
 *
 * @license GPL-2.0-or-later
 */
interface TermCleaner {

	/**
	 * Delete the given term IDs.
	 * Ensuring that they are unreferenced is the caller’s responsibility.
	 *
	 * Depending on the implementation,
	 * this may include further internal cleanups.
	 * In that case, the implementation takes care
	 * that those cleanups do not affect other (not deleted) term IDs.
	 *
	 * @param int[] $termInLangIds
	 */
	public function cleanTerms( array $termInLangIds );

}
