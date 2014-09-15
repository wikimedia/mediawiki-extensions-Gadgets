<?php

/**
 * @group Database
 */
class ForeignDBGadgetRepoTest extends DatabaseBasedGadgetRepoTestCase {

	/**
	 * @return PHPUnit_Framework_MockObject_MockObject|ForeignDBGadgetRepo
	 */
	private function getMockRepo() {
		/** @var PHPUnit_Framework_MockObject_MockObject|ForeignDBGadgetRepo $r */
		$r = $this->getMockBuilder( 'ForeignDBGadgetRepo' )
			->disableOriginalConstructor()
			->setMethods( array( 'getMasterDB' ) )
			->getMock();
		$r->setCache( new EmptyBagOStuff() );
		$r->expects( $this->any() )->method( 'getMasterDB' )
			->will( $this->returnValue( wfGetDB( DB_MASTER ) ) );

		return $r;
	}
	/**
	 * @covers ForeignDBGadgetRepo::loadAllData
	 */
	public function testLoadAllData() {
		$r = $this->getMockRepo();
		$ids = $r->getGadgetIds();
		$this->assertEquals( array( 'sharedgadget1' ), $ids );
		$this->assertSharedGadget1( $r->getGadget( 'sharedgadget1' ) );
	}

	/**
	 * @covers ForeignDBGadgetRepo::loadDataFor
	 */
	public function testLoadDataFor() {
		$r = $this->getMockRepo();
		$this->assertSharedGadget1( $r->getGadget( 'sharedgadget1' ) );

		$this->assertNull( $r->getGadget( 'thisdoesntexist' ) );
		// Gadget in the db but not shared
		$this->assertNull( $r->getGadget( 'unittestgadget1' ) );
	}

}