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
 * Class representing the user-specific options for a gadget
 */
class GadgetOptionsResourceLoaderModule extends ResourceLoaderModule {
	private $gadget;

	/**
	 * Creates an instance of this class
	 * @param $gadget Gadget: the gadget this module is built upon.
	 */
	public function __construct( $gadget ) {
		$this->gadget = $gadget;
	}

	/**
	 * Overrides ResourceLoaderModule::getDependencies()
	 * @return Array: Names of resources this module depends on
	 */
	public function getDependencies() {
		return array( 'ext.gadgets' );
	}
	
	/**
	 * Overrides ResourceLoaderModule::getGroup()
	 * @return String
	 */
	public function getGroup() {
		return 'private';
	}

	/**
	 * Overrides ResourceLoaderModule::getScript()
	 * @param $context ResourceLoaderContext
	 * @return String
	 */
	public function getScript( ResourceLoaderContext $context ) {
		$gadgetInfo = array(
			'name'   => $this->gadget->getName(),
			'config' => $this->gadget->getPrefs()
		);
		return Xml::encodeJsCall( 'mw.gadgets.info.set', 
			array( $this->gadget->getName(), $gadgetInfo ) );
	}
	
	/**
	 * Overrides ResourceLoaderModule::getModifiedTime()
	 * @param $context ResourceLoaderContext
	 * @return Integer
	 */
	public function getModifiedTime( ResourceLoaderContext $context ) {
		$prefsMTime = $this->gadget->getPrefsTimestamp();

		$resourceLoader = $context->getResourceLoader();
		$parentModule = $resourceLoader->getModule( $this->gadget->getModuleName() );
		$gadgetMTime = $parentModule->getModifiedTime( $context );

		return max( $gadgetMTime, $prefsMTime );
	}
}

