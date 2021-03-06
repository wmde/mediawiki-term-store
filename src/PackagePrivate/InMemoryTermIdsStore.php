<?php

namespace Wikibase\TermStore\MediaWiki\PackagePrivate;

class InMemoryTermIdsStore implements TermIdsAcquirer, TermIdsResolver, TermCleaner {

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

	public function resolveTermIds( array $termIds ): array {
		$terms = [];
		foreach ( $this->terms as $type => $termsOfType ) {
			foreach ( $termsOfType as $lang => $termsOfLang ) {
				foreach ( $termsOfLang as $term => $id ) {
					if ( in_array( $id, $termIds ) ) {
						$terms[$type][$lang][] = $term;
					}
				}
			}
		}
		return $terms;
	}

	public function cleanTerms( array $termInLangIds ) {
		$termInLangIdsAsKeys = array_flip( $termInLangIds );
		foreach ( $this->terms as $type => &$termsOfType ) {
			foreach ( $termsOfType as $lang => &$termsOfLang ) {
				foreach ( $termsOfLang as $term => $id ) {
					if ( array_key_exists( $id, $termInLangIdsAsKeys ) ) {
						unset( $termsOfLang[$term] );
					}
				}
				if ( $termsOfLang === [] ) {
					unset( $termsOfType[$lang] );
				}
			}
			if ( $termsOfType === [] ) {
				unset( $this->terms[$type] );
			}
		}
	}

}
