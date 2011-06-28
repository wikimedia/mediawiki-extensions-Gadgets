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
 * Class representing a list of resources for one gadget
 */
class GadgetResourceLoaderModule extends ResourceLoaderWikiModule {
	private $pages, $dependencies, $gadget;

	/**
	 * Creates an instance of this class
	 * @param $pages Array: Associative array of pages in ResourceLoaderWikiModule-compatible
	 * format, for example:
	 * array(
	 * 		'MediaWiki:Gadget-foo.js'  => array( 'type' => 'script' ),
	 * 		'MediaWiki:Gadget-foo.css' => array( 'type' => 'style' ),
	 * )
	 * @param $dependencies Array: Names of resources this module depends on
	 */
	public function __construct( $pages, $dependencies, $gadget ) {
		$this->pages = $pages;
		$this->dependencies = $dependencies;
		$this->gadget = $gadget;
	}

	/**
	 * Overrides the abstract function from ResourceLoaderWikiModule class
	 * @return Array: $pages passed to __construct()
	 */
	protected function getPages( ResourceLoaderContext $context ) {
		return $this->pages;
	}

	/**
	 * Overrides ResourceLoaderModule::getDependencies()
	 * @return Array: Names of resources this module depends on
	 */
	public function getDependencies() {
		return $this->dependencies;
	}
	
	public function getScript( ResourceLoaderContext $context ) {
		$prefs = $this->gadget->getPrefs();
		
		//Enclose gadget's code in a closure, with "this" bound to the
		//configuration object (or to "window" for non-configurable gadgets)
		$header = '(function(){';
		
		//TODO: it may be nice add other metadata for the gadget
		$boundObject = array( 'config' => $prefs );
		
		if ( $prefs !== NULL ) {
			//Bind configuration object to "this".
			$footer = '}).' . Xml::encodeJsCall( 'apply', 
				array( $boundObject, array() )
			) . ';';
		} else {
			//Bind window to "this"
			$footer = '}).apply( window, [] );';
		}
		
		return $header . parent::getScript( $context ) . $footer;
	}
	
	public function getModifiedTime( ResourceLoaderContext $context ) {
		$touched = wfTimestamp( TS_UNIX, RequestContext::getMain()->getUser()->getTouched() );
		$gadgetMTime = $this->gadget->getModifiedTime();
		return max( parent::getModifiedTime( $context ), $touched, $gadgetMTime );
	}
}

