<?php

namespace Wikibase\TermStore\MediaWiki\PackagePrivate;

class InMemoryTermIdsAcquirer implements TermIdsAcquirer {

	private $terms = [];
	private $lastId = 0;

	public function acquireTermIds( array $termsArray ): array {
		$ids = [];

		foreach ( $termsArray as $type => $termsOfType ) {
			foreach ( $termsOfType as $lang => $terms ) {
				$terms = (array)$terms;

				foreach ( $terms as $term ) {
					if ( !isset( $this->terms[$type][$lang][$term] ) ) {
						$this->terms[$type][$lang][$term] = ++$this->lastId;
					}

					$ids[] = $this->terms[$type][$lang][$term];
				}
			}
		}

		return $ids;
	}

}
