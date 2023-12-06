<?php

use MediaWiki\Extension\Gadgets\Gadget;
use Wikimedia\TestingAccessWrapper;

/**
 * @group Gadgets
 */
class GadgetResourceLoaderModuleTest extends MediaWikiIntegrationTestCase {
	use GadgetTestTrait;

	/**
	 * @var Gadget
	 */
	private $gadget;
	/**
	 * @var TestingAccessWrapper
	 */
	private $gadgetModule;

	protected function setUp(): void {
		parent::setUp();
		$this->gadget = $this->makeGadget( '*foo [ResourceLoader|package]|foo.js|foo.css|foo.json' );
		$this->gadgetModule = $this->makeGadgetModule( $this->gadget );
	}

	/**
	 * @covers \MediaWiki\Extension\Gadgets\GadgetResourceLoaderModule::getPages
	 */
	public function testGetPages() {
		$pages = $this->gadgetModule->getPages( ResourceLoaderContext::newDummyContext() );
		$this->assertArrayHasKey( 'MediaWiki:Gadget-foo.css', $pages );
		$this->assertArrayHasKey( 'MediaWiki:Gadget-foo.js', $pages );
		$this->assertArrayHasKey( 'MediaWiki:Gadget-foo.json', $pages );
		$this->assertArrayEquals( $pages, [
			[ 'type' => 'style' ],
			[ 'type' => 'script' ],
			[ 'type' => 'data' ]
		] );

		$nonPackageGadget = $this->makeGadget( '*foo [ResourceLoader]|foo.js|foo.css|foo.json' );
		$nonPackageGadgetModule = $this->makeGadgetModule( $nonPackageGadget );
		$this->assertArrayNotHasKey( 'MediaWiki:Gadget-foo.json',
			$nonPackageGadgetModule->getPages( ResourceLoaderContext::newDummyContext() ) );
	}

}
