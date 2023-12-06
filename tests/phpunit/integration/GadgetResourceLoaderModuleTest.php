<?php

use MediaWiki\Extension\Gadgets\Gadget;
use MediaWiki\Extension\Gadgets\GadgetResourceLoaderModule;
use MediaWiki\Extension\Gadgets\StaticGadgetRepo;
use MediaWiki\MainConfigNames;
use MediaWiki\ResourceLoader as RL;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \MediaWiki\Extension\Gadgets\GadgetResourceLoaderModule
 * @group Gadgets
 * @group Database
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

	public function testEs6Gadget() {
		$this->editPage( 'MediaWiki:Gadget-foo.js', '(() => {})();' );
		$repo = new StaticGadgetRepo( [
			'g1' => $this->makeGadget( '*g1 [ResourceLoader]|foo.js' ),
			'g2' => $this->makeGadget( '*g1 [ResourceLoader|requiresES6]|foo.js' )
		] );
		$this->setService( 'GadgetsRepo', $repo );
		$this->overrideConfigValue( MainConfigNames::ResourceLoaderValidateJS, true );
		$rlContext = RL\Context::newDummyContext();

		$m1 = new GadgetResourceLoaderModule( [ 'id' => 'g1' ] );
		$this->assertFalse( $m1->requiresES6() );
		$m1->setConfig( $this->getServiceContainer()->getMainConfig() );
		$this->assertStringContainsString( 'mw.log.error', $m1->getScript( $rlContext ) );

		$m2 = new GadgetResourceLoaderModule( [ 'id' => 'g2' ] );
		$this->assertTrue( $m2->requiresES6() );
		$m2->setConfig( $this->getServiceContainer()->getMainConfig() );
		$this->assertStringNotContainsString( 'mw.log.error', $m2->getScript( $rlContext ) );
	}

}
