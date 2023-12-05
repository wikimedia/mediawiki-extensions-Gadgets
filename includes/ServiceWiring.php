<?php

use MediaWiki\Extension\Gadgets\GadgetRepo;
use MediaWiki\Extension\Gadgets\MediaWikiGadgetsDefinitionRepo;
use MediaWiki\Extension\Gadgets\MediaWikiGadgetsJsonRepo;
use MediaWiki\MediaWikiServices;

return [
	'GadgetsRepo' => static function ( MediaWikiServices $services ): GadgetRepo {
		$wanCache = $services->getMainWANObjectCache();
		$revisionLookup = $services->getRevisionLookup();
		switch ( $services->getMainConfig()->get( 'GadgetsRepo' ) ) {
			case 'definition':
				return new MediaWikiGadgetsDefinitionRepo( $wanCache, $revisionLookup );
			case 'json':
				return new MediaWikiGadgetsJsonRepo( $wanCache, $revisionLookup );
			default:
				throw new InvalidArgumentException( 'Unexpected value for $wgGadgetsRepo' );
		}
	},
];
