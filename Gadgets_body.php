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

class GadgetHooks {

	/**
	 * ArticleSaveComplete hook handler.
	 * 
	 * @param $article Article
	 * @param $user User
	 * @param $text String: New page text
	 */
	public static function articleSaveComplete( $article, $user, $text ) {
		//update cache if MediaWiki:Gadgets-definition was edited
		$title = $article->mTitle;
		if( $title->getNamespace() == NS_MEDIAWIKI && $title->getText() == 'Gadgets-definition' ) {
			Gadget::loadStructuredList( $text );
		}
		return true;
	}

	/**
	 * GetPreferences hook handler.
	 * @param $user User
	 * @param $preferences Array: Preference descriptions
	 */
	public static function getPreferences( $user, &$preferences ) {
		$gadgets = Gadget::loadStructuredList();
		if (!$gadgets) return true;
		
		$options = array();
		$default = array();
		foreach( $gadgets as $section => $thisSection ) {
			$available = array();
			foreach( $thisSection as $gadget ) {
				if ( $gadget->isAllowed( $user ) ) {
					$gname = $gadget->getName();
					$available[$gadget->getDescription()] = $gname;
					if ( $gadget->isEnabled( $user ) ) {
						$default[] = $gname;
					}
				}
			}
			if ( $section !== '' ) {
				$section = wfMsgExt( "gadget-section-$section", 'parseinline' );
				if ( count ( $available ) ) {
					$options[$section] = $available;
				}
			} else {
				$options = array_merge( $options, $available );
			}
		}
		
		$preferences['gadgets-intro'] =
			array(
				'type' => 'info',
				'label' => '&#160;',
				'default' => Xml::tags( 'tr', array(),
					Xml::tags( 'td', array( 'colspan' => 2 ),
						wfMsgExt( 'gadgets-prefstext', 'parse' ) ) ),
				'section' => 'gadgets',
				'raw' => 1,
				'rawrow' => 1,
			);
		
		$preferences['gadgets'] = 
			array(
				'type' => 'multiselect',
				'options' => $options,
				'section' => 'gadgets',
				'label' => '&#160;',
				'prefix' => 'gadget-',
				'default' => $default,
			);
			
		return true;
	}

	/**
	 * ResourceLoaderRegisterModules hook handler.
	 * @param $resourceLoader ResourceLoader
	 */
	public static function registerModules( &$resourceLoader ) {
		$gadgets = Gadget::loadList();
		if ( !$gadgets ) {
			return true;
		}
		foreach ( $gadgets as $g ) {
			$module = $g->getModule();
			if ( $module ) {
				$resourceLoader->register( $g->getModuleName(), $module );
			}
		}
		return true;
	}

	/**
	 * BeforePageDisplay hook handler.
	 * @param $out OutputPage
	 */
	public static function beforePageDisplay( $out ) {
		global $wgUser;
		
		wfProfileIn( __METHOD__ );

		//tweaks in Special:Preferences
		if ( $out->getTitle()->isSpecial( 'Preferences' ) ) {
			$out->addModules( 'ext.gadgets.preferences' );
		}

		$gadgets = Gadget::loadList();
		if ( !$gadgets ) {
			wfProfileOut( __METHOD__ );
			return true;
		}

		$lb = new LinkBatch();
		$lb->setCaller( __METHOD__ );
		$pages = array();

		foreach ( $gadgets as $gadget ) {
			if ( $gadget->isEnabled( $wgUser ) && $gadget->isAllowed( $wgUser ) ) {
				if ( $gadget->hasModule() ) {
					$out->addModules( $gadget->getModuleName() );
				}
				foreach ( $gadget->getLegacyScripts() as $page ) {
					$lb->add( NS_MEDIAWIKI, $page );
					$pages[] = $page;
				}
			}
		}

		$lb->execute( __METHOD__ );

		$done = array();
		foreach ( $pages as $page ) {
			if ( isset( $done[$page] ) ) continue;
			$done[$page] = true;
			self::applyScript( $page, $out );
		}
		wfProfileOut( __METHOD__ );

		return true;
	}

	/**
	 * UserLoadOptions hook handler.
	 * @param $user
	 * @param &$options 
	 */
	public static function userLoadOptions( $user, &$options ) {
		//Remove gadget-*-config options, since they must not be delivered like other user preferences
		foreach ( $options as $option => $value ){
			//TODO: Regexsp not coherent with current gadget's naming rules
			if ( preg_match( '/gadget-[a-zA-Z][a-zA-Z0-9_]*-config/', $option ) ) {
				//TODO: cache them before unsetting
				unset( $options[$option] );
			}
		}
		return true;
	}


	/**
	 * Adds one legacy script to output.
	 * 
	 * @param $page String: Unprefixed page title
	 * @param $out OutputPage
	 */
	private static function applyScript( $page, $out ) {
		global $wgJsMimeType;

		# bug 22929: disable gadgets on sensitive pages.  Scripts loaded through the
		# ResourceLoader handle this in OutputPage::getModules()
		# TODO: make this extension load everything via RL, then we don't need to worry
		# about any of this.
		if( $out->getAllowedModules( ResourceLoaderModule::TYPE_SCRIPTS ) < ResourceLoaderModule::ORIGIN_USER_SITEWIDE ){
			return;
		}

		$t = Title::makeTitleSafe( NS_MEDIAWIKI, $page );
		if ( !$t ) return;

		$u = $t->getLocalURL( 'action=raw&ctype=' . $wgJsMimeType );
		$out->addScriptFile( $u, $t->getLatestRevID() );
	}

	/**
	 * UnitTestsList hook handler
	 * @param $files Array: List of extension test files
	 */
	public static function unitTestsList( $files ) {
		$files[] = dirname( __FILE__ ) . '/Gadgets_tests.php';
		return true;
	}
}

/**
 * Wrapper for one gadget.
 */
class Gadget {
	/**
	 * Increment this when changing class structure
	 */
	const GADGET_CLASS_VERSION = 5;

	private $version = self::GADGET_CLASS_VERSION,
	        $scripts = array(),
	        $styles = array(),
			$dependencies = array(),
	        $name,
			$definition,
			$resourceLoaded = false,
			$requiredRights = array(),
			$onByDefault = false,
			$category;


	//Mandatory arguments for any kind of preferences
	private static $prefsMandatoryArgs = array(
			'type',
			'default'
		);

	//Other mandatory arguments for specific types
	private static $prefsMandatoryArgsByType = array(
			'select' => array( 'options' ),
			'selectorother' => array( 'options' ),
			'selectandother' => array( 'options' ),
			'multiselect' => array( 'options' )
		);

	/**
	 * Creates an instance of this class from definition in MediaWiki:Gadgets-definition
	 * @param $definition String: Gadget definition
	 * @return Mixed: Instance of Gadget class or false if $definition is invalid
	 */
	public static function newFromDefinition( $definition ) {
		$m = array();
		if ( !preg_match( '/^\*+ *([a-zA-Z](?:[-_:.\w\d ]*[a-zA-Z0-9])?)(\s*\[.*?\])?\s*((\|[^|]*)+)\s*$/', $definition, $m ) ) {
			return false;
		}
		//NOTE: the gadget name is used as part of the name of a form field,
		//      and must follow the rules defined in http://www.w3.org/TR/html4/types.html#type-cdata
		//      Also, title-normalization applies.
		$gadget = new Gadget();
		$gadget->name = trim( str_replace(' ', '_', $m[1] ) );
		$gadget->definition = $definition;
		$options = trim( $m[2], ' []' );
		foreach ( preg_split( '/\s*\|\s*/', $options, -1, PREG_SPLIT_NO_EMPTY ) as $option ) {
			$arr  = preg_split( '/\s*=\s*/', $option, 2 );
			$option = $arr[0];
			if ( isset( $arr[1] ) ) {
				$params = explode( ',', $arr[1] );
				$params = array_map( 'trim', $params );
			} else {
				$params = array();
			}
			switch ( $option ) {
				case 'ResourceLoader':
					$gadget->resourceLoaded = true;
					break;
				case 'dependencies':
					$gadget->dependencies = $params;
					break;
				case 'rights':
					$gadget->requiredRights = $params;
					break;
				case 'default':
					$gadget->onByDefault = true;
					break;
			}
		}
		foreach ( preg_split( '/\s*\|\s*/', $m[3], -1, PREG_SPLIT_NO_EMPTY ) as $page ) {
			$page = "Gadget-$page";
			if ( preg_match( '/\.js/', $page ) ) {
				$gadget->scripts[] = $page;
			} elseif ( preg_match( '/\.css/', $page ) ) {
				$gadget->styles[] = $page;
			}
		}
		return $gadget;
	}

	/**
	 * @return String: Gadget name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return String: Gadget description parsed into HTML
	 */
	public function getDescription() {
		return wfMessage( "gadget-{$this->getName()}" )->parse();
	}

	/**
	 * @return String: Wikitext of gadget description
	 */
	public function getRawDescription() {
		return wfMessage( "gadget-{$this->getName()}" )->plain();
	}

	/**
	 * @return String: Name of category (aka section) our gadget belongs to. Empty string if none.
	 */
	public function getCategory() {
		return $this->category;
	}

	/**
	 * @return String: Name of ResourceLoader module for this gadget
	 */
	public function getModuleName() {
		return "ext.gadget.{$this->name}";
	}

	/**
	 * Checks whether this is an instance of an older version of this class deserialized from cache
	 * @return Boolean
	 */
	public function isOutdated() {
		return $this->version != self::GADGET_CLASS_VERSION;
	}

	/**
	 * Checks whether this gadget is enabled for given user
	 *
	 * @param $user User: user to check against
	 * @return Boolean
	 */
	public function isEnabled( $user ) {
		return (bool)$user->getOption( "gadget-{$this->name}", $this->onByDefault );
	}

	/**
	 * Checks whether given user has permissions to use this gadget
	 *
	 * @param $user User: user to check against
	 * @return Boolean
	 */
	public function isAllowed( $user ) {
		return count( array_intersect( $this->requiredRights, $user->getRights() ) ) == count( $this->requiredRights );
	}

	/**
	 * @return Boolean: Whether this gadget is on by default for everyone (but can be disabled in preferences)
	 */
	public function isOnByDefault() {
		return $this->onByDefault;
	}

	/**
	 * @return Boolean: Whether all of this gadget's JS components support ResourceLoader
	 */
	public function supportsResourceLoader() {
		return $this->resourceLoaded;
	}

	/**
	 * @return Boolean: Whether this gadget has resources that can be loaded via ResourceLoader
	 */
	public function hasModule() {
		return count( $this->styles ) 
			+ ( $this->supportsResourceLoader() ? count( $this->scripts ) : 0 ) 
				> 0;
	}

	/**
	 * @return String: Definition for this gadget from MediaWiki:gadgets-definition
	 */
	public function getDefinition() {
		return $this->definition;
	}

	/**
	 * @return Array: Array of pages with JS not prefixed with namespace
	 */
	public function getScripts() {
		return $this->scripts;
	}

	/**
	 * @return Array: Array of pages with CSS not prefixed with namespace
	 */
	public function getStyles() {
		return $this->styles;
	}

	/**
	 * @return Array: Array of all of this gadget's resources
	 */
	public function getScriptsAndStyles() {
		return array_merge( $this->scripts, $this->styles );
	}

	/**
	 * Returns module for ResourceLoader, see getModuleName() for its name.
	 * If our gadget has no scripts or styles suitable for RL, false will be returned.
	 * @return Mixed: GadgetResourceLoaderModule or false
	 */
	public function getModule() {
		$pages = array();
		foreach( $this->styles as $style ) {
			$pages['MediaWiki:' . $style] = array( 'type' => 'style' );
		}
		if ( $this->supportsResourceLoader() ) {
			foreach ( $this->scripts as $script ) {
				$pages['MediaWiki:' . $script] = array( 'type' => 'script' );
			}
		}
		if ( !count( $pages ) ) {
			return null;
		}
		return new GadgetResourceLoaderModule( $pages, $this->dependencies );
	}

	/**
	 * Returns list of scripts that don't support ResourceLoader
	 * @return Array
	 */
	public function getLegacyScripts() {
		if ( $this->supportsResourceLoader() ) {
			return array();
		}
		return $this->scripts;
	}

	/**
	 * Returns names of resources this gadget depends on
	 * @return Array
	 */
	public function getDependencies() {
		return $this->dependencies;
	}

	/**
	 * Returns array of permissions required by this gadget
	 * @return Array
	 */
	public function getRequiredRights() {
		return $this->requiredRights;
	}

	/**
	 * Loads and returns a list of all gadgets
	 * @return Mixed: Array of gadgets or false
	 */
	public static function loadList() {
		static $gadgets = null;

		if ( $gadgets !== null ) return $gadgets;

		wfProfileIn( __METHOD__ );
		$struct = self::loadStructuredList();
		if ( !$struct ) {
			$gadgets = $struct;
			wfProfileOut( __METHOD__ );
			return $gadgets;
		}

		$gadgets = array();
		foreach ( $struct as $section => $entries ) {
			$gadgets = array_merge( $gadgets, $entries );
		}
		wfProfileOut( __METHOD__ );

		return $gadgets;
	}

	/**
	 * Checks whether gadget list from cache can be used.
	 * @return Boolean
	 */
	private static function isValidList( $gadgets ) {
		if ( !is_array( $gadgets ) ) return false;
		// Check if we have 1) array of gadgets 2) the gadgets are up to date
		// One check is enough
		foreach ( $gadgets as $section => $list ) {
			foreach ( $list as $g ) {
				if ( !( $g instanceof Gadget ) || $g->isOutdated() ) {
					return false;
				} else {
					return true;
				}
			}
		}
		return true; // empty array
	}

	/**
	 * Loads list of gadgets and returns it as associative array of sections with gadgets
	 * e.g. array( 'sectionnname1' => array( $gadget1, $gadget2),
	 *             'sectionnname2' => array( $gadget3 ) );
	 * @param $forceNewText String: New text of MediaWiki:gadgets-sdefinition. If specified, will
	 * 	      force a purge of cache and recreation of the gadget list.
	 * @return Mixed: Array or false
	 */
	public static function loadStructuredList( $forceNewText = null ) {
		global $wgMemc;

		static $gadgets = null;
		if ( $gadgets !== null && $forceNewText === null ) return $gadgets;

		wfProfileIn( __METHOD__ );
		$key = wfMemcKey( 'gadgets-definition', self::GADGET_CLASS_VERSION );

		if ( $forceNewText === null ) {
			//cached?
			$gadgets = $wgMemc->get( $key );
			if ( self::isValidList( $gadgets ) ) {
				wfProfileOut( __METHOD__ );
				return $gadgets;
			}

			$g = wfMsgForContentNoTrans( "gadgets-definition" );
			if ( wfEmptyMsg( "gadgets-definition", $g ) ) {
				$gadgets = false;
				wfProfileOut( __METHOD__ );
				return $gadgets;
			}
		} else {
			$g = $forceNewText;
		}

		$g = preg_replace( '/<!--.*-->/s', '', $g );
		$g = preg_split( '/(\r\n|\r|\n)+/', $g );

		$gadgets = array();
		$section = '';

		foreach ( $g as $line ) {
			$m = array();
			if ( preg_match( '/^==+ *([^*:\s|]+?)\s*==+\s*$/', $line, $m ) ) {
				$section = $m[1];
			}
			else {
				$gadget = self::newFromDefinition( $line );
				if ( $gadget ) {
					$gadgets[$section][$gadget->getName()] = $gadget;
					$gadget->category = $section;
				}
			}
		}

		//cache for a while. gets purged automatically when MediaWiki:Gadgets-definition is edited
		$wgMemc->set( $key, $gadgets, 60*60*24 );
		$source = $forceNewText !== null ? 'input text' : 'MediaWiki:Gadgets-definition';
		wfDebug( __METHOD__ . ": $source parsed, cache entry $key updated\n");
		wfProfileOut( __METHOD__ );

		return $gadgets;
	}
	
	
	//TODO: put the following static methods somewhere else
	
	public static function isGadgetPrefsDescriptionValid( $prefsJson ) {
		$prefs = FormatJson::decode( $prefsJson, true );
		
		if ( $prefs === null ) {
			return false;
		}
		
		//TODO: improve validation
		foreach ( $prefs as $option => $optionDefinition ) {
			foreach ( self::$prefsMandatoryArgs as $arg ) {
				if ( !isset( $optionDefinition[$arg] ) ) {
					return false;
				}
			}
			
			$type = $option['type'];
			
			if ( isset( self::$prefsMandatoryArgsByType[$type] ) ) {
				foreach ( self::$prefsMandatoryArgsByType[$type] as $arg ) {
					if ( !isset( $optionDefinition[$arg] ) ) {
						return false;
					}
				}
			}
		}
		
		return true;
	}
	
	//Gets preferences for gadget $gadget;
	// returns * null if the gadget doesn't exists
	//         * '' if the gadget exists but doesn't have any preferences
	//         * the preference description in JSON format, otherwise
	public static function getGadgetPrefsDescription( $gadget ) {
		$gadgetsList = Gadget::loadStructuredList();
		foreach ( $gadgetsList as $sectionName => $gadgets ) {
			foreach ( $gadgets as $gadgetName => $gadgetData ) {
				if ( $gadgetName == $gadget ) {
					//Gadget found; are there any prefs?
					
					$prefsMsg = "Gadget-" . $gadget . ".preferences";
					
					//TODO: should we cache?
					
					$prefsJson = wfMsgForContentNoTrans( $prefsMsg );
					if ( wfEmptyMsg( $prefsMsg, $prefsJson ) ) {
						return null;
					}
					
					if ( !self::isGadgetPrefsDescriptionValid( $prefsJson ) ){
						return '';
					}
					
					return $prefsJson;
				}
			}
		}
		return null; //gadget not found
	}

	//Get user's preferences for a specific gadget
	public static function getUserPrefs( $user, $gadget ) {
		//TODO: cache!

		$prefsDescriptionJson = Gadget::getGadgetPrefsDescription( $gadget );
		
		if ( $prefsDescriptionJson === null || $prefsDescriptionJson === '' ) {
			return null;
		}
		
		$prefsDescription = FormatJson::decode( $prefsDescriptionJson, true );
		
		
		$dbr = wfGetDB( DB_SLAVE );
		
		$id = $user->getId();
		$property = "gadget-{$gadget}-config";

		$res = $dbr->selectRow(
			'user_properties',
			array('up_value'),
			array(
				"up_user={$id}",
				"up_property='{$property}'"
			),
			__METHOD__);
		
		
		if ( !$res ) {
			return null;
		}
		
		$userPrefsJson = $res->up_value;
		
		$userPrefs = FormatJson::decode( $userPrefsJson, true );
		
		//TODO: validate against description, fix mismatches
		
		return $userPrefs;
	}

	//Set user's preferences for a specific gadget
	public static function setUserPrefs( $user, $gadget, $preferences ) {
		//TODO: proper param checking
		
		$preferencesJson = FormatJson::encode( $preferences );
		
		$dbw = wfGetDB( DB_MASTER );
		
		$id = $user->getId();
		$property = "gadget-{$gadget}-config";
		
		$row = array(
				'up_user'     => $id,
				'up_property' => $property,
				'up_value'    => $preferencesJson
			);
				
		//Could probably be done with the "ON DUPLICATE KEY UPDATE" syntax
		
		$res = $dbw->update(
			'user_properties',
			$row,
			array(
				"up_user={$id}",
				"up_property='{$property}'"
			),
			__METHOD__
		);


		$rc = $dbw->affectedRows();
		if ( $rc == 0 ) {
			$dbw->insert(
				'user_properties',
				$row,
				__METHOD__,
				array( 'IGNORE' ) //ignore insertions without any changes
			);
		}

		//Invalidate cache and update user_touched
		$user->invalidateCache( true );
	}

}

/**
 * Class representing a list of resources for one gadget
 */
class GadgetResourceLoaderModule extends ResourceLoaderWikiModule {
	private $pages, $dependencies;

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
	public function __construct( $pages, $dependencies ) {
		$this->pages = $pages;
		$this->dependencies = $dependencies;
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
		//return array_merge( $this->dependencies, array( 'mediawiki.user' ) );
		return $this->dependencies;
	}
	
	public function getScript( ResourceLoaderContext $context ) {
		$moduleName = $this->getName();
		$gadget = substr( $moduleName, strlen( 'ext.gadget.' ) );
		
		
		$user = RequestContext::getMain()->getUser();
		
		$prefs = Gadget::getUserPrefs( $user, $gadget );
		
		//Enclose gadget's code in a closure, with "this" bound to the
		//configuration object (or to "window" for non-configurable gadgets)
		$header = '(function(){';
		
		$boundObject = array( 'config' => $prefs );
		
		if ( $prefs !== NULL ) {
			//Bind configuration object to "this".
			//TODO: would be nice add other metadata for the gadget
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
		$touched = RequestContext::getMain()->getUser()->getTouched();
		
		return max( parent::getModifiedTime( $context ), $touched );
	}
}

//Implements ext.gadgets. Required by ext.gadgets.preferences
class GadgetsGlobalModule extends ResourceLoaderModule {
	//TODO: should override getModifiedTime()
	
	public function getScript( ResourceLoaderContext $context ) {
		$configurableGadgets = array();
		$gadgetsList = Gadget::loadStructuredList();
		
		foreach ( $gadgetsList as $sectionName => $gadgets ) {
			foreach ( $gadgets as $gadget => $gadget_data ) {
				$prefs = Gadget::getGadgetPrefsDescription( $gadget );
				if ( $prefs !== null && $prefs !== '' ) {
					$configurableGadgets[] = $gadget;
				}
			}
		}

		$script = "mw.gadgets = {}\n";
		$script .= "mw.gadgets.configurableGadgets = " . Xml::encodeJsVar( $configurableGadgets ) . ";\n";
		return $script;
	}
}

