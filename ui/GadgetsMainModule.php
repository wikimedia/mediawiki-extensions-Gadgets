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
	//TODO: should override getModifiedTime()
	
	public function getScript( ResourceLoaderContext $context ) {
		$configurableGadgets = array();
		$gadgetsList = Gadget::loadStructuredList();
		
		foreach ( $gadgetsList as $section => $gadgets ) {
			foreach ( $gadgets as $gadgetName => $gadget ) {
				$prefs = $gadget->getPrefsDescription();
				if ( $prefs !== null ) {
					$configurableGadgets[] = $gadget->getName();
				}
			}
		}

		$script = "mw.gadgets = {}\n";
		$script .= "mw.gadgets.configurableGadgets = " . Xml::encodeJsVar( $configurableGadgets ) . ";\n";
		return $script;
	}
}
