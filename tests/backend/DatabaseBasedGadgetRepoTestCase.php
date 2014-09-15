<?php

/**
 * @group Database
 */
abstract class DatabaseBasedGadgetRepoTestCase extends MediaWikiTestCase {

	public function setUp() {
		parent::setUp();
		// Disable all object caching
		$this->setMwGlobals( 'wgMemc', new EmptyBagOStuff() );
		$this->createGadgets();
	}

	/**
	 * @param Gadget $g
	 */
	protected function assertSharedGadget1( $g ) {
		$this->assertInstanceOf( 'Gadget', $g );
		$this->assertTrue( $g->isHidden() );
		$this->assertTrue( $g->isShared() );
		$this->assertEquals( '20120915102257', $g->getTimestamp() );
		$this->assertEquals( array(), $g->getDependencies() );
		$this->assertEquals( 'top', $g->getPosition() );
	}

	/**
	 * @param Gadget $g
	 */
	protected function assertUnittestGadget1( $g ) {
		$this->assertInstanceOf( 'Gadget', $g );
		$this->assertTrue( $g->isHidden() );
		$this->assertFalse( $g->isShared() );
		$this->assertEquals( array( 'mediawiki.notification' ), $g->getDependencies() );
		$this->assertEquals( '20140915102257', $g->getTimestamp() );
		$this->assertEquals( 'bottom', $g->getPosition() );
	}

	/**
	 * Puts some fake gadgets in the database
	 * The properties should be kept in-sync with the
	 * above two asserts.
	 */
	private function createGadgets() {
		$rows = array(
			array(
				'gd_id' => 'unittestgadget1',
				'gd_blob' => '{"settings":{"rights":[],"default":false,"hidden":true,"shared":false,"category":"","skins":true},"module":{"scripts":["Foobar.js"],"styles":["Foobar.css"],"dependencies":["mediawiki.notification"],"messages":[],"position":"bottom"}}',
				'gd_timestamp' => '20140915102257',
				'gd_shared' => '0',
			),
			array(
				'gd_id' => 'sharedgadget1',
				'gd_blob' => '{"settings":{"rights":[],"default":false,"hidden":true,"shared":true,"category":"","skins":true},"module":{"scripts":["Foobar.js"],"styles":["Foobar.css"],"dependencies":[],"messages":[],"position":"top"}}',
				'gd_timestamp' => '20120915102257',
				'gd_shared' => '1',
			),
		);

		$dbw = wfGetDB( DB_MASTER );
		// Clear out anything in the unittest_gadgets table...
		$dbw->delete( 'gadgets', '*', __METHOD__ );

		$dbw->insert( 'gadgets', $rows, __METHOD__ );
	}
}
