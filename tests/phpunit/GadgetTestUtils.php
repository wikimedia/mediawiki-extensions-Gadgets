<?php

use Wikimedia\TestingAccessWrapper;

class GadgetTestUtils {
	/**
	 * @param string $line
	 * @return Gadget
	 */
	public static function makeGadget( $line ) {
		$repo = new MediaWikiGadgetsDefinitionRepo();
		$g = $repo->newFromDefinition( $line, 'misc' );
		return $g;
	}

	public static function makeGadgetModule( Gadget $g ) {
		$module = TestingAccessWrapper::newFromObject(
			new GadgetResourceLoaderModule( [ 'id' => null ] )
		);
		$module->gadget = $g;
		return $module;
	}
}
