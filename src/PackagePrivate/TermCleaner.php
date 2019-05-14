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
	 * @param int[] $termInLangIds
	 */
	public function cleanTerms( array $termInLangIds );

}
