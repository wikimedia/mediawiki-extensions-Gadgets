<?php

use MediaWiki\Extension\Gadgets\GadgetRepo;
use MediaWiki\MediaWikiServices;

return [
	'GadgetsRepo' => static function ( MediaWikiServices $services ): GadgetRepo {
		/** @var $repo GadgetRepo */
		$repo = $services->getObjectFactory()->createObject( $services->getMainConfig()->get( 'GadgetsRepoClass' ) );
		return $repo;
	},
];
