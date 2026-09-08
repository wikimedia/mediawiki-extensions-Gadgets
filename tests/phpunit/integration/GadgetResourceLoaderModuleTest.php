<?php

use MediaWiki\Extension\Gadgets\Gadget;
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

	/** @var Gadget */
	private $gadget;
	/** @var TestingAccessWrapper */
	private $gadgetModule;

	protected function setUp(): void {
		parent::setUp();
		$this->gadget = $this->makeGadget( '*foo [package]|foo.js|foo.css|foo.json' );
		$this->gadgetModule = $this->makeGadgetModule( $this->gadget );
		$this->overrideConfigValue( MainConfigNames::ResourceLoaderValidateJS, true );
	}

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

		$nonPackageGadget = $this->makeGadget( '*foo|foo.js|foo.css|foo.json' );
		$nonPackageGadgetModule = $this->makeGadgetModule( $nonPackageGadget );
		$this->assertArrayNotHasKey( 'MediaWiki:Gadget-foo.json',
			$nonPackageGadgetModule->getPages( $context ) );
	}

	public function testCodexIcons() {
		$context = $this->createMock( RL\Context::class );
		$g = $this->makeGadget( '*foo [package|codexIcons=cdxIconInfo]|foo.js|foo.vue' );
		$this->assertEquals( [ 'cdxIconInfo' ], $g->getCodexIcons() );
		$module = $this->makeGadgetModule( $g );
		$module->getConfig()->set( MainConfigNames::CodexDevelopmentDir, null );
		$content = $module->getScript( $context );
		$this->assertArrayHasKey( 'icons.json', $content['files'] );
		$this->assertArrayHasKey( 'cdxIconInfo', $content['files']['icons.json']['content'] );
	}
}
