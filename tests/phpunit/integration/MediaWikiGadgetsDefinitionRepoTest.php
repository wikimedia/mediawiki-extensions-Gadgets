<?php

use MediaWiki\Extension\Gadgets\MediaWikiGadgetsDefinitionRepo;

/**
 * @covers \MediaWiki\Extension\Gadgets\MediaWikiGadgetsDefinitionRepo
 * @group Gadgets
 * @group Database
 */
class MediaWikiGadgetsDefinitionRepoTest extends MediaWikiIntegrationTestCase {

	public function testGadgetsDefinitionRepo() {
		$gadgetsDef = <<<EOT
* foo | foo.js
==keep-section1==
* bar| bar.js
==remove-section==
* baz [rights=read] |baz.js
==keep-section2==
* quux [rights=read] | quux.js
* g1 [ResourceLoader | default | namespaces=2 | rights=editmyuserjs] | g1.js <!-- comment -->
* g2 [ResourceLoader | default | namespaces=2 | rights=editmyuserjs] | <!-- comment --> g2.js
EOT;
		$this->editPage( 'MediaWiki:Gadgets-definition', $gadgetsDef );

		$services = $this->getServiceContainer();
		$repo = new MediaWikiGadgetsDefinitionRepo( $services->getMainWANObjectCache(),
			$services->getRevisionLookup() );
		$gadgets = $repo->fetchStructuredList();
		$this->assertCount( 6, $gadgets );

		$bar = $repo->getGadget( 'bar' );
		$this->assertEquals( 'keep-section1', $bar->toArray()['category'] );

		$this->assertEquals( [ 'MediaWiki:Gadget-g1.js' ], $repo->getGadget( 'g1' )->getScripts() );
		$this->assertEquals( [ 'MediaWiki:Gadget-g2.js' ], $repo->getGadget( 'g2' )->getScripts() );
	}

	/**
	 * @covers \MediaWiki\Extension\Gadgets\Hooks::onPageSaveComplete
	 * @covers \MediaWiki\Extension\Gadgets\Hooks::onPageDeleteComplete
	 * @covers \MediaWiki\Extension\Gadgets\GadgetRepo::handlePageUpdate
	 * @covers \MediaWiki\Extension\Gadgets\MediaWikiGadgetsDefinitionRepo
	 */
	public function testCacheInvalidationOnSave() {
		$services = $this->getServiceContainer();
		$wanCache = new WANObjectCache( [ 'cache' => new HashBagOStuff ] );
		$wanCache->useInterimHoldOffCaching( false );

		$repo = new MediaWikiGadgetsDefinitionRepo( $wanCache, $services->getRevisionLookup() );
		$this->setService( 'GadgetsRepo', $repo );

		$this->editPage( 'MediaWiki:Gadgets-definition', '* X1[ResourceLoader|default]|foo.js' );
		$this->assertEquals( [ 'X1' ], $repo->getGadgetIds() );
		$this->assertTrue( $repo->getGadget( 'X1' )->isOnByDefault() );

		$this->editPage( 'MediaWiki:Gadgets-definition', "* X1[ResourceLoader|default]|foo.js\n" .
			"* X2[ResourceLoader|default]|foo.css" );
		$this->assertEquals( [ 'X1', 'X2' ], $repo->getGadgetIds() );

		// Disable X1 by default
		$this->editPage( 'MediaWiki:Gadgets-definition', "* X1[ResourceLoader]|foo.js\n" .
			"* X2[ResourceLoader|default]|foo.css" );
		$this->assertFalse( $repo->getGadget( 'X1' )->isOnByDefault() );

		$this->deletePage( $services->getWikiPageFactory()->newFromTitle(
			Title::newFromText( 'MediaWiki:Gadgets-definition' ) ) );
		$this->assertEquals( [], $repo->getGadgetIds() );
	}

}
