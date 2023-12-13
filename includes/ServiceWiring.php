<?php

use MediaWiki\Extension\Gadgets\GadgetDefinitionNamespaceRepo;
use MediaWiki\Extension\Gadgets\GadgetRepo;
use MediaWiki\Extension\Gadgets\MediaWikiGadgetsDefinitionRepo;
use MediaWiki\MediaWikiServices;

return [
	'GadgetsRepo' => static function ( MediaWikiServices $services ): GadgetRepo {
		$wanCache = $services->getMainWANObjectCache();
		$revisionLookup = $services->getRevisionLookup();
		switch ( $services->getMainConfig()->get( 'GadgetsRepo' ) ) {
			case 'definition':
				return new MediaWikiGadgetsDefinitionRepo( $wanCache, $revisionLookup );
			case 'json':
				return new GadgetDefinitionNamespaceRepo( $wanCache, $revisionLookup );
			default:
				throw new InvalidArgumentException( 'Unexpected value for $wgGadgetsRepo' );
		}
	},
];
