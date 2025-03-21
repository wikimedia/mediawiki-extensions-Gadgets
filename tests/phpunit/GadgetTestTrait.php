<?php

use MediaWiki\Extension\Gadgets\Gadget;
use MediaWiki\Extension\Gadgets\GadgetResourceLoaderModule;
use MediaWiki\Extension\Gadgets\MediaWikiGadgetsDefinitionRepo;
use MediaWiki\Revision\RevisionLookup;
use Wikimedia\ObjectCache\HashBagOStuff;
use Wikimedia\ObjectCache\WANObjectCache;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\TestingAccessWrapper;

/**
 * Utility functions for testing gadgets.
 *
 * This trait is intended to be used on subclasses of MediaWikiUnitTestCase
 * or MediaWikiIntegrationTestCase.
 */
trait GadgetTestTrait {
	/**
	 * @param string $line
	 * @return Gadget
	 */
	public function makeGadget( string $line ) {
		$dbProvider = $this->createMock( IConnectionProvider::class );
		$wanCache = WANObjectCache::newEmpty();
		$revLookup = $this->createMock( RevisionLookup::class );
		$srvCache = new HashBagOStuff();
		$repo = new MediaWikiGadgetsDefinitionRepo( $dbProvider, $wanCache, $revLookup, $srvCache );
		return $repo->newFromDefinition( $line, 'misc' );
	}

	/**
	 * @param Gadget $g
	 * @return GadgetResourceLoaderModule
	 */
	public function makeGadgetModule( Gadget $g ) {
		$module = TestingAccessWrapper::newFromObject(
			new GadgetResourceLoaderModule( [ 'id' => null ] )
		);
		$module->gadget = $g;
		return $module;
	}

}
