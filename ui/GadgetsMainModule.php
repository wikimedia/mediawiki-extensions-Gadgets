<?php

/**
 * Gadgets extension - lets users select custom javascript gadgets
 *
 *
 * For more info see http://mediawiki.org/wiki/Extension:Gadgets
 *
 * @file
 * @ingroup Extensions
 * @author Daniel Kinzler, brightbyte.de
 * @copyright © 2007 Daniel Kinzler
 * @license GNU General Public Licence 2.0 or later
 */

/**
 * Class implementing the ext.gadgets module. Required by ext.gadgets.preferences.
 */
class GadgetsMainModule extends ResourceLoaderModule {
	
	public function getModifiedTime( ResourceLoaderContext $context ) {
		$gadgets = Gadget::loadList();
		
		$m = 0;
		foreach ( $gadgets as $gadget ) {
			$m = max( $m, $gadget->getModifiedTime() );
		}
		return $m;
	}
	
	public function getScript( ResourceLoaderContext $context ) {
		$configurableGadgets = array();
		$gadgets = Gadget::loadList();
		
		foreach ( $gadgets as $gadget ) {
			if ( $gadget->getPrefsDescription() !== null ) {
				$configurableGadgets[] = $gadget->getName();
			}
		}

		$script = "mw.gadgets = {}\n";
		$script .= "mw.gadgets.configurableGadgets = " . Xml::encodeJsVar( $configurableGadgets ) . ";\n";
		return $script;
	}
}
