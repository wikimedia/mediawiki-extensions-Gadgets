<?php

/**
 * Mainly just tests that GadgetResourceLoaderModule just calls
 * the corresponding function for the underlying Gadget object
 *
 * @group Gadgets
 * @group Extensions
 */
class GadgetResourceLoaderModuleTest extends MediaWikiTestCase {

	/**
	 * @return PHPUnit_Framework_MockObject_MockObject|Gadget
	 */
	private function getMockGadget() {
		$g = $this->getMockBuilder( 'Gadget' )
			->disableOriginalConstructor()
			->getMock();
		$g->expects( $this->any() )->method( 'getDependencies' )->will( $this->returnValue( array(
			'test.bar', 'test.baz',
		) ) );
		$g->expects( $this->any() )->method( 'getPages' )->will( $this->returnValue( array(
			'Gadget:Foo.js' => array( 'type' => 'script' ),
			'Gadget:Baz.js' => array( 'type' => 'script' ),
			'Gadget:Foo.css' => array( 'type' => 'style' ),
			'Gadget:Baz.css' => array( 'type' => 'style' ),
		) ) );
		$g->expects( $this->any() )->method( 'getMessages' )->will( $this->returnValue( array(
			'gadget-foo', 'gadget-bar', 'gadget-baz',
		) ) );
		$g->expects( $this->any() )->method( 'getPosition' )->will( $this->returnValue( 'top' ) );

		$repo = $this->getMockForAbstractClass( 'GadgetRepo' );
		$repo->expects( $this->any() )->method( 'getSource' )->will( $this->returnValue( 'testsource' ) );
		$g->expects( $this->any() )->method( 'getRepo' )->will( $this->returnValue( $repo ) );

		return $g;
	}

	/**
	 * @return PHPUnit_Framework_MockObject_MockObject|ResourceLoaderContext
	 */
	private function getMockRLContext() {
		return $this->getMockBuilder( 'ResourceLoaderContext' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testGetPages() {
		$g = $this->getMockGadget();
		$module = new GadgetResourceLoaderModule(
			array( 'gadget' => $g )
		);

		$this->assertEquals( $g->getPages(), $module->getPages( $this->getMockRLContext() ) );
	}

	/**
	 * @covers GadgetResourceLoaderModule::getDependencies
	 */
	public function testGetDependencies() {
		$g = $this->getMockGadget();
		$module = new GadgetResourceLoaderModule(
			array( 'gadget' => $g )
		);

		$this->assertEquals( $g->getDependencies(), $module->getDependencies() );
	}

	/**
	 * @covers GadgetResourceLoaderModule::getMessages
	 */
	public function testGetMessages() {
		$g = $this->getMockGadget();
		$module = new GadgetResourceLoaderModule(
			array( 'gadget' => $g )
		);

		$this->assertEquals( $g->getMessages(), $module->getMessages() );
	}

	/**
	 * @covers GadgetResourceLoaderModule::getSource
	 */
	public function testGetSource() {
		$g = $this->getMockGadget();
		$module = new GadgetResourceLoaderModule(
			array( 'gadget' => $g )
		);

		$this->assertEquals( 'testsource', $module->getSource() );
	}

	/**
	 * @covers GadgetResourceLoaderModule::getPosition
	 */
	public function testGetPosition() {
		$g = $this->getMockGadget();
		$module = new GadgetResourceLoaderModule(
			array( 'gadget' => $g )
		);

		$this->assertEquals( $g->getPosition(), $module->getPosition() );

	}
}