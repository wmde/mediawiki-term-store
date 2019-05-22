<?php

namespace Wikibase\TermStore\MediaWiki\Tests\Integration\PackagePrivate\Util;

use PHPUnit\Framework\TestCase;
use Wikibase\TermStore\MediaWiki\PackagePrivate\Util\ReplicaMasterAwareRecordIdsAcquirer;
use Wikibase\TermStore\MediaWiki\Tests\Util\FakeLoadBalancer;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\DatabaseSqlite;

class ReplicaMasterAwareRecordIdsAcquirerTest extends TestCase {

	const TABLE_DDL_FILE_PATH = __DIR__ . '/ReplicaMasterAwareRecordIdsAcquirerTest_tableDDL.sql';
	const TABLE_NAME = 'replica_master_aware_record_ids_acquirer_test';
	const ID_COLUMN = 'id';

	/**
	 * @var IDatabase $dbMaster
	 */
	private $dbMaster;

	/**
	 * @var IDatabase $dbReplica
	 */
	private $dbReplica;

	public function setUp() {
		$this->dbMaster = DatabaseSqlite::newStandaloneInstance( ':memory:' );
		$this->dbMaster->sourceFile( self::TABLE_DDL_FILE_PATH );

		$this->dbReplica = DatabaseSqlite::newStandaloneInstance( ':memory:' );
		$this->dbReplica->sourceFile( self::TABLE_DDL_FILE_PATH );
	}

	public function testWhenAllRecordsExistInReplica() {
		$records = $this->getTestRecords();

		$this->dbReplica->insert(
			self::TABLE_NAME,
			$records
		);
		$this->assertSameRecordsInDb( $records, $this->dbReplica );

		$idsAcquirer = $this->getTestSubjectInstance();
		$acquiredRecordsWithIds = $idsAcquirer->acquireIds( $records );

		$this->assertNoRecordsInDb( $records, $this->dbMaster );
		$this->assertSameRecordsInDb( $acquiredRecordsWithIds, $this->dbReplica );
	}

	public function testWhenAllRecordsExistInMaster() {
		$records = $this->getTestRecords();

		$this->dbMaster->insert(
			self::TABLE_NAME,
			$records
		);
		$this->assertSameRecordsInDb( $records, $this->dbMaster );

		$idsAcquirer = $this->getTestSubjectInstance();
		$acquiredRecordsWithIds = $idsAcquirer->acquireIds( $records );

		$this->assertNoRecordsInDb( $records, $this->dbReplica );
		$this->assertSameRecordsInDb( $acquiredRecordsWithIds, $this->dbMaster );
	}

	public function testWhenAllRecordsDoNotExistInReplicaOrMaster() {
		$records = $this->getTestRecords();

		$idsAcquirer = $this->getTestSubjectInstance();
		$acquiredRecordsWithIds = $idsAcquirer->acquireIds( $records );

		$this->assertNoRecordsInDb( $records, $this->dbReplica );
		$this->assertSameRecordsInDb( $acquiredRecordsWithIds, $this->dbMaster );
	}

	public function testWhenSomeRecordsDoNotExistInReplicaButExistInMaster() {
		$records = $this->getTestRecords();

		$recordsInReplica = [ $records[0], $records[1] ];
		$recordsInMaster = [ $records[2] ];

		$this->dbReplica->insert(
			self::TABLE_NAME,
			$recordsInReplica
		);
		$this->assertSameRecordsInDb( $recordsInReplica, $this->dbReplica );

		$this->dbMaster->insert(
			self::TABLE_NAME,
			$recordsInMaster
		);
		$this->assertSameRecordsInDb( $recordsInMaster, $this->dbMaster );

		$idsAcquirer = $this->getTestSubjectInstance();
		$acquiredRecordsWithIds = $idsAcquirer->acquireIds( $records );

		$this->assertSame(
			count( $acquiredRecordsWithIds ),
			count( $records )
		);
		$this->assertSameRecordsInDb( [ $records[3] ], $this->dbMaster );
		$this->assertNoRecordsInDb( $recordsInReplica, $this->dbMaster );
		$this->assertNoRecordsInDb( $recordsInMaster, $this->dbReplica );
	}

	private function assertNoRecordsInDb( array $records, IDatabase $db ) {
		$recordsInDbCount = $db->selectRowCount(
			self::TABLE_NAME,
			'*',
			$this->recordsToSelectConditions( $records, $db )
		);

		$this->assertSame( 0, $recordsInDbCount );
	}

	private function assertSameRecordsInDb( array $records, IDatabase $db ) {
		$recordsInDbCount = $db->selectRowCount(
			self::TABLE_NAME,
			'*',
			$this->recordsToSelectConditions( $records, $db )
		);

		$this->assertCount( $recordsInDbCount, $records );
	}

	private function recordsToSelectConditions( array $records, IDatabase $db ) {
		$conditionsPairs = [];
		foreach ( $records as $record ) {
			$conditionPairs[] = $db->makeList( $record, IDatabase::LIST_AND );
		}

		return $db->makeList( $conditionPairs, IDatabase::LIST_OR );
	}

	private function getTestSubjectInstance() {
		return new ReplicaMasterAwareRecordIdsAcquirer(
			new FakeLoadBalancer( [
				'dbr' => $this->dbReplica,
				'dbw' => $this->dbMaster,
			] ),
			self::TABLE_NAME,
			self::ID_COLUMN
		);
	}

	private function getTestRecords() {
		return [
			[ 'column_value' => 'valueA1', 'column_id' => '1' ],
			[ 'column_value' => 'valueA2', 'column_id' => '2' ],
			[ 'column_value' => 'valueA3', 'column_id' => '3' ],
			[ 'column_value' => 'valueA4', 'column_id' => '4' ]
		];
	}
}

