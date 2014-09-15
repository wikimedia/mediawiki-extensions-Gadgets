<?php

/**
 * @group Database
 */
class LocalGadgetRepoTest extends DatabaseBasedGadgetRepoTestCase {

	/**
	 * @covers LocalGadgetRepo::__construct
	 */
	public function testConstructor() {
		// Takes no arguments
		$r = new LocalGadgetRepo();
		$this->assertInstanceOf( 'LocalGadgetRepo', $r );
	}

	/**
	 * @covers LocalGadgetRepo::loadAllData
	 */
	public function testLoadAllData() {
		$r = new LocalGadgetRepo();
		$ids = $r->getGadgetIds();
		sort( $ids );
		$this->assertEquals( array( 'sharedgadget1', 'unittestgadget1' ), $ids );
		$this->assertSharedGadget1( $r->getGadget( 'sharedgadget1' ) );
		$this->assertUnittestGadget1( $r->getGadget( 'unittestgadget1' ) );
	}

	/**
	 * @covers LocalGadgetRepo::loadDataFor
	 */
	public function testLoadDataFor() {
		$r = new LocalGadgetRepo();
		$this->assertUnittestGadget1( $r->getGadget( 'unittestgadget1' ) );

		$this->assertNull( $r->getGadget( 'thisdoesntexist' ) );
	}
}
