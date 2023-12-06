<?php

use MediaWiki\Extension\Gadgets\GadgetDefinitionNamespaceRepo;

/**
 * @group Gadgets
 * @group Database
 */
class GadgetDefinitionNamespaceRepoTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers \MediaWiki\Extension\Gadgets\GadgetDefinitionNamespaceRepo
	 */
	public function testGetGadget() {
		$this->editPage( 'Gadget definition:Test',
			'{"module":{"scripts":["test.js"]}, "settings":{"default":true}}' );

		$services = $this->getServiceContainer();
		$repo = new GadgetDefinitionNamespaceRepo( $services->getMainWANObjectCache(), $services->getRevisionLookup() );
		$gadget = $repo->getGadget( 'Test' );
		$this->assertTrue( $gadget->isOnByDefault() );
		$this->assertArrayEquals( [ "Gadget:test.js" ], $gadget->getScripts() );
	}

	/**
	 * @covers \MediaWiki\Extension\Gadgets\GadgetDefinitionNamespaceRepo
	 */
	public function testGetGadgetIds() {
		$this->editPage( 'Gadget definition:X1',
			'{"module":{"scripts":["Gadget:test.js"]}, "settings":{"default":true}}' );
		$this->editPage( 'Gadget definition:X2',
			'{"module":{"scripts":["Gadget:test.js"]}, "settings":{"default":true}}' );

		$services = $this->getServiceContainer();
		$wanCache = $services->getMainWANObjectCache();
		$repo = new GadgetDefinitionNamespaceRepo( $wanCache, $services->getRevisionLookup() );
		$wanCache->clearProcessCache();
		$this->assertArrayEquals( [ 'X1', 'X2' ], $repo->getGadgetIds() );
	}
}
