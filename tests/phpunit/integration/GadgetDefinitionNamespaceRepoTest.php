<?php

use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\Extension\Gadgets\Content\GadgetDefinitionContent;
use MediaWiki\Extension\Gadgets\GadgetDefinitionNamespaceRepo;
use MediaWiki\Revision\SlotRecord;

/**
 * @group Gadgets
 * @group Database
 */
class GadgetDefinitionNamespaceRepoTest extends MediaWikiIntegrationTestCase {

	private function createGadgetDefinitionPage( string $title, string $content ) {
		$services = $this->getServiceContainer();
		$page = $services->getWikiPageFactory()->newFromTitle( Title::newFromText( $title ) );
		$updater = $page->newPageUpdater( $this->getTestUser()->getUser() );
		$updater->setContent( SlotRecord::MAIN, new GadgetDefinitionContent( $content ) );
		$updater->saveRevision( CommentStoreComment::newUnsavedComment( "" ) );
	}

	/**
	 * @covers \MediaWiki\Extension\Gadgets\GadgetDefinitionNamespaceRepo
	 */
	public function testGetGadget() {
		$this->createGadgetDefinitionPage( 'Gadget definition:Test',
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
		$this->createGadgetDefinitionPage( 'Gadget definition:X1',
			'{"module":{"scripts":["Gadget:test.js"]}, "settings":{"default":true}}' );
		$this->createGadgetDefinitionPage( 'Gadget definition:X2',
			'{"module":{"scripts":["Gadget:test.js"]}, "settings":{"default":true}}' );

		$services = $this->getServiceContainer();
		$repo = new GadgetDefinitionNamespaceRepo( $services->getMainWANObjectCache(), $services->getRevisionLookup() );
		$this->assertArrayEquals( [ 'X1', 'X2' ], $repo->getGadgetIds() );
	}
}
