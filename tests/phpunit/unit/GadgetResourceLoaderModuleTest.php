<?php

use MediaWiki\Extension\Gadgets\Gadget;
use MediaWiki\ResourceLoader as RL;
use Wikimedia\TestingAccessWrapper;

/**
 * @group Gadgets
 */
class GadgetResourceLoaderModuleTest extends MediaWikiUnitTestCase {
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
		$context = $this->createMock( RL\Context::class );
		$pages = $this->gadgetModule->getPages( $context );
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
			$nonPackageGadgetModule->getPages( $context ) );
	}

}
