<?php

namespace Wikibase\TermStore\MediaWiki\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wikibase\TermStore\MediaWiki\ProductionClass;

class ProductionClassTest extends TestCase {

	public function testGetTrue() {
		$this->assertTrue( ProductionClass::getTrue() );
	}

}
