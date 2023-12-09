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
EOT;
		$this->editPage( 'MediaWiki:Gadgets-definition', $gadgetsDef );

		$services = $this->getServiceContainer();
		$repo = new MediaWikiGadgetsDefinitionRepo( $services->getMainWANObjectCache(),
			$services->getRevisionLookup() );
		$gadgets = $repo->fetchStructuredList();
		$this->assertCount( 4, $gadgets );

		$bar = $repo->getGadget( 'bar' );
		$this->assertEquals( 'keep-section1', $bar->toArray()['category'] );
	}

}
