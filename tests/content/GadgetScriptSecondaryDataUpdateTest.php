<?php

class GadgetScriptSecondaryDataUpdateTest extends MediaWikiTestCase {

	/**
	 * @covers GadgetScriptSecondaryDataUpdate::__construct
	 */
	public function testConstructor() {
		$title = $this->getMock( 'Title' );
		$upd = new GadgetScriptSecondaryDataUpdate( $title, 'css' );
		$this->assertInstanceOf( 'GadgetScriptSecondaryDataUpdate', $upd );
		$this->setExpectedException( 'InvalidArgumentException' );
		new GadgetScriptSecondaryDataUpdate( $title, 'notcssnorjs' );
	}
}