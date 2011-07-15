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
	 * @param $gadget Gadget: the gadget this module is built upon.
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
		$deps = array( 'ext.gadgets' );
		if ( $this->gadget->getPrefsDescription() !== null ){
			$deps[] = $this->gadget->getPrefsModuleName();
		}
		
		return array_merge(
				$this->dependencies,
				$deps
			);
	}
	
	/**
	 * Overrides ResourceLoaderModule::getScript()
	 * @param $context ResourceLoaderContext
	 * @return String
	 */
	public function getScript( ResourceLoaderContext $context ) {
		$prefs = $this->gadget->getPrefs();
		
		//Enclose gadget's code in a closure, with "this" bound to the
		//configuration object (or to "window" for non-configurable gadgets)
		$header = "(function(){";
		
		if ( $prefs !== null ) {
			//Bind gadget info to "this".
			$footer = "}).apply( mw.gadgets.info.get('{$this->gadget->getName()}') );";
		} else {
			//Bind window to "this"
			$footer = "}).apply( window );";
		}
		
		return $header . parent::getScript( $context ) . $footer;
	}
	
	/**
	 * Overrides ResourceLoaderModule::getModifiedTime()
	 * @param $context ResourceLoaderContext
	 * @return Integer
	 */
	public function getModifiedTime( ResourceLoaderContext $context ) {
		//TODO: should also depend on the mTime of preferences description page
		return parent::getModifiedTime( $context );
	}
}

