<?php

namespace Wikibase\TermStore\MediaWiki\PackagePrivate;

use InvalidArgumentException;
use stdClass;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * Term ID resolver using the normalized database schema.
 *
 * @license GPL-2.0-or-later
 */
class DatabaseTermIdsResolver implements TermIdsResolver {

	/** @var TypeIdsResolver */
	private $typeIdsResolver;

	/** @var ILoadBalancer */
	private $lb;

	/** @var IDatabase */
	private $dbr = null;

	/** @var IDatabase */
	private $dbw = null;

	public function __construct(
		TypeIdsResolver $typeIdsResolver,
		ILoadBalancer $lb
	) {
		$this->typeIdsResolver = $typeIdsResolver;
		$this->lb = $lb;
	}

	/*
	 * Term data is first read from the replica; if that returns less rows than we asked for,
	 * then there are some new rows in the master that were not yet replicated, and we fall back
	 * to the master. As the internal relations of the term store never change (for example,
	 * a term_in_lang row will never suddenly point to a different text_in_lang), a master fallback
	 * should never be necessary in any other case. However, callers need to consider where they
	 * got the list of term IDs they pass into this method from: if it’s from a replica, they may
	 * still see outdated data overall.
	 */
	public function resolveTermIds( array $termIds ): array {
		$terms = [];

		$replicaResult = $this->selectTerms( $this->getDbr(), $termIds );
		$types = $this->loadTypes( $replicaResult );
		$replicaTermIds = [];

		foreach ( $replicaResult as $row ) {
			$replicaTermIds[] = $row->wbtl_id;
			$this->addResultTerms( $terms, $row, $types );
		}

		if ( count( $replicaTermIds ) !== count( $termIds ) ) {
			$masterTermIds = array_values( array_diff( $termIds, $replicaTermIds ) );
			$masterResult = $this->selectTerms( $this->getDbw(), $masterTermIds );
			$types += $this->loadTypes( $masterResult );
			foreach ( $masterResult as $row ) {
				$this->addResultTerms( $terms, $row, $types );
			}
		}

		return $terms;
	}

	private function selectTerms( IDatabase $db, array $termIds ): IResultWrapper {
		return $db->select(
			[ 'wbt_term_in_lang', 'wbt_text_in_lang', 'wbt_text' ],
			[ 'wbtl_id', 'wbtl_type_id', 'wbxl_language', 'wbx_text' ],
			[
				'wbtl_id' => $termIds,
				// join conditions
				'wbtl_text_in_lang_id=wbxl_id',
				'wbxl_text_id=wbx_id',
			],
			__METHOD__
		);
	}

	private function loadTypes( IResultWrapper $result ) {
		$typeIds = [];
		foreach ( $result as $row ) {
      if ( !( $typeIds[$row->wbtl_type_id] ?? false ) ) {
				$typeIds[$row->wbtl_type_id] = true;
      }
		}
		return $this->typeIdsResolver->resolveTypeIds( array_keys( $typeIds ) );
	}

	private function addResultTerms( array &$terms, stdClass $row, array $types ) {
		$typeId = $row->wbtl_type_id;
    $type = $types[$typeId] ?? null;
		if ( $type === null ) {
			throw new InvalidArgumentException(
				'Type ID ' . $typeId . ' was not found!' );
		}

		$lang = $row->wbxl_language;
		$text = $row->wbx_text;

		$terms[$type][$lang][] = $text;
	}

	private function getDbr() {
		if ( $this->dbr === null ) {
			$this->dbr = $this->lb->getConnection( ILoadBalancer::DB_REPLICA );
		}

		return $this->dbr;
	}

	private function getDbw() {
		if ( $this->dbw === null ) {
			$this->dbw = $this->lb->getConnection( ILoadBalancer::DB_MASTER );
		}

		return $this->dbw;
	}

}
