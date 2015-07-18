<?php
/**
 * Gadgets extension - lets users select custom javascript gadgets
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
 * Wrapper for one gadget.
 */
class Gadget {
	/**
	 * Increment this when changing class structure
	 */
	const GADGET_CLASS_VERSION = 8;

	const CACHE_TTL = 86400;

	private $scripts = array(),
			$styles = array(),
			$dependencies = array(),
			$name,
			$definition,
			$resourceLoaded = false,
			$requiredRights = array(),
			$requiredSkins = array(),
			$targets = array( 'desktop' ),
			$onByDefault = false,
			$position = 'bottom',
			$category;

	/** @var array|bool Result of loadStructuredList() */
	private static $definitionCache;

	public function __construct( array $options ) {
		foreach ( $options as $member => $option ) {
			switch ( $member ) {
				case 'scripts':
				case 'styles':
				case 'dependencies':
				case 'name':
				case 'definition':
				case 'resourceLoaded':
				case 'requiredRights':
				case 'requiredSkins':
				case 'targets':
				case 'onByDefault':
				case 'position':
				case 'category':
					$this->{$member} = $option;
					break;
				default:
					throw new InvalidArgumentException( "Unrecognized '$member' parameter" );
			}
		}
	}

	/**
	 * Whether the provided gadget id is valid
	 *
	 * @param string $id
	 * @return bool
	 */
	public static function isValidGadgetID( $id ) {
		return strlen( $id ) > 0 && ResourceLoader::isValidModuleName( "ext.gadget.$id" );
	}

	/**
	 * Creates an instance of this class from definition in MediaWiki:Gadgets-definition
	 * @param $definition String: Gadget definition
	 * @return Gadget|bool Instance of Gadget class or false if $definition is invalid
	 */
	public static function newFromDefinition( $definition ) {
		$m = array();
		if ( !preg_match( '/^\*+ *([a-zA-Z](?:[-_:.\w\d ]*[a-zA-Z0-9])?)(\s*\[.*?\])?\s*((\|[^|]*)+)\s*$/', $definition, $m ) ) {
			return false;
		}
		// NOTE: the gadget name is used as part of the name of a form field,
		//      and must follow the rules defined in http://www.w3.org/TR/html4/types.html#type-cdata
		//      Also, title-normalization applies.
		$info = array();
		$info['name'] = trim( str_replace( ' ', '_', $m[1] ) );
		// If the name is too long, then RL will throw an MWException when
		// we try to register the module
		if ( !self::isValidGadgetID( $info['name'] ) ) {
			return false;
		}
		$info['definition'] = $definition;
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
					$info['resourceLoaded'] = true;
					break;
				case 'dependencies':
					$info['dependencies'] = $params;
					break;
				case 'rights':
					$info['requiredRights'] = $params;
					break;
				case 'skins':
					$info['requiredSkins'] = $params;
					break;
				case 'default':
					$info['onByDefault'] = true;
					break;
				case 'targets':
					$info['targets'] = $params;
					break;
				case 'top':
					$info['position'] = 'top';
					break;
			}
		}

		foreach ( preg_split( '/\s*\|\s*/', $m[3], -1, PREG_SPLIT_NO_EMPTY ) as $page ) {
			$page = "Gadget-$page";

			if ( preg_match( '/\.js/', $page ) ) {
				$info['scripts'][] = $page;
			} elseif ( preg_match( '/\.css/', $page ) ) {
				$info['styles'][] = $page;
			}
		}

		return new Gadget( $info );
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
		return count( array_intersect( $this->requiredRights, $user->getRights() ) ) == count( $this->requiredRights )
			&& ( !count( $this->requiredSkins ) || in_array( $user->getOption( 'skin' ), $this->requiredSkins ) );
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

		foreach ( $this->styles as $style ) {
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

		return new GadgetResourceLoaderModule( $pages, $this->dependencies, $this->targets, $this->position );
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
	 * Returns array of skins where this gadget works
	 * @return Array
	 */
	public function getRequiredSkins() {
		return $this->requiredSkins;
	}

	/**
	 * Returns the position of this Gadget's ResourceLoader module
	 * @return String: 'bottom' or 'top'
	 */
	public function getPosition() {
		return $this->position;
	}

	/**
	 * Loads and returns a list of all gadgets
	 * @return Mixed: Array of gadgets or false
	 */
	public static function loadList() {
		static $gadgets = null;

		if ( $gadgets !== null ) {
			return $gadgets;
		}

		$struct = self::loadStructuredList();

		if ( !$struct ) {
			$gadgets = $struct;
			return $gadgets;
		}

		$gadgets = array();
		foreach ( $struct as $entries ) {
			$gadgets = array_merge( $gadgets, $entries );
		}

		return $gadgets;
	}

	/**
	 * Loads list of gadgets and returns it as associative array of sections with gadgets
	 * e.g. array( 'sectionnname1' => array( $gadget1, $gadget2 ),
	 *             'sectionnname2' => array( $gadget3 ) );
	 * @return array|bool Gadget array or false on failure
	 */
	public static function loadStructuredList() {
		if ( self::$definitionCache !== null ) {
			return self::$definitionCache; // process cache hit
		}

		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'gadgets' );
		// Ideally $t1Cache is APC, and $wanCache is memcached
		$t1Cache = $config->get( 'GadgetsCacheType' )
			? ObjectCache::getInstance( $config->get( 'GadgetsCacheType' ) )
			: ObjectCache::getInstance( CACHE_NONE );
		$wanCache = ObjectCache::getMainWANInstance();

		$key = wfMemcKey( 'gadgets-definition', self::GADGET_CLASS_VERSION );

		if ( $config->get( 'GadgetsCaching' ) ) {
			// (a) Check the tier 1 cache
			$value = $t1Cache->get( $key );
			// Check if it passes a blind TTL check (avoids I/O)
			if ( $value && ( microtime( true ) - $value['time'] ) < 10 ) {
				self::$definitionCache = $value['gadgets']; // process cache
				return self::$definitionCache;
			}
			// Cache generated after the "check" time should be up-to-date
			$ckTime = $wanCache->getCheckKeyTime( $key ) + WANObjectCache::HOLDOFF_TTL;
			if ( $value && $value['time'] > $ckTime ) {
				self::$definitionCache = $value['gadgets']; // process cache
				return self::$definitionCache;
			}

			// (b) Fetch value from WAN cache or regenerate if needed.
			// This is hit occasionally and more so when the list changes.
			$value = $wanCache->getWithSetCallback(
				$key,
				function( $old, &$ttl ) {
					$now = microtime( true );
					$gadgets = Gadget::fetchStructuredList();
					if ( $gadgets === false ) {
						$ttl = WANObjectCache::TTL_UNCACHEABLE;
					}

					return array( 'gadgets' => $gadgets, 'time' => $now );
				},
				self::CACHE_TTL,
				array( $key ),
				array( 'lockTSE' => 300 )
			);

			// Update the tier 1 cache as needed
			if ( $value['gadgets'] !== false && $value['time'] > $ckTime ) {
				// Set a modest TTL to keep the WAN key in cache
				$t1Cache->set( $key, $value, mt_rand( 300, 600 ) );
			}

			self::$definitionCache = $value['gadgets']; // process cache
		} else {
			self::$definitionCache = self::fetchStructuredList(); // process cache
		}

		return self::$definitionCache;
	}

	/**
	 * Fetch list of gadgets and returns it as associative array of sections with gadgets
	 * e.g. array( 'sectionnname1' => array( $gadget1, $gadget2 ),
	 *             'sectionnname2' => array( $gadget3 ) );
	 * @param $forceNewText String: Injected text of MediaWiki:gadgets-definition [optional]
	 * @return array|bool
	 */
	public static function fetchStructuredList( $forceNewText = null ) {
		if ( $forceNewText === null ) {
			$g = wfMessage( "gadgets-definition" )->inContentLanguage();
			if ( !$g->exists() ) {
				return false; // don't cache
			}

			$g = $g->plain();
		} else {
			$g = $forceNewText;
		}

		$gadgets = self::listFromDefinition( $g );
		if ( !count( $gadgets ) ) {
			return false; // don't cache; Bug 37228
		}

		$source = $forceNewText !== null ? 'input text' : 'MediaWiki:Gadgets-definition';
		wfDebug( __METHOD__ . ": $source parsed, cache entry should be updated\n" );

		return $gadgets;
	}

	/**
	 * Generates a structured list of Gadget objects from a definition
	 *
	 * @param $definition
	 * @return array Array( category => Array( name => Gadget ) )
	 */
	private static function listFromDefinition( $definition ) {
		$definition = preg_replace( '/<!--.*?-->/s', '', $definition );
		$lines = preg_split( '/(\r\n|\r|\n)+/', $definition );

		$gadgets = array();
		$section = '';

		foreach ( $lines as $line ) {
			$m = array();
			if ( preg_match( '/^==+ *([^*:\s|]+?)\s*==+\s*$/', $line, $m ) ) {
				$section = $m[1];
			} else {
				$gadget = self::newFromDefinition( $line );
				if ( $gadget ) {
					$gadgets[$section][$gadget->getName()] = $gadget;
					$gadget->category = $section;
				}
			}
		}

		return $gadgets;
	}

	/**
	 * Update MediaWiki:Gadgets-definition cache
	 */
	public static function purgeDefinitionCache() {
		$key = wfMemcKey( 'gadgets-definition', Gadget::GADGET_CLASS_VERSION );
		ObjectCache::getMainWANInstance()->touchCheckKey( $key );
	}

	/**
	 * Inject gadgets into the process cache for unit tests
	 *
	 * @param array|bool $cache Result of fetchStructuredList()
	 */
	public static function injectDefinitionCache( $cache ) {
		self::$definitionCache = $cache;
	}
}

/**
 * Class representing a list of resources for one gadget
 */
class GadgetResourceLoaderModule extends ResourceLoaderWikiModule {
	private $pages, $dependencies;

	/**
	 * Creates an instance of this class
	 *
	 * @param $pages Array: Associative array of pages in ResourceLoaderWikiModule-compatible
	 * format, for example:
	 * array(
	 *        'MediaWiki:Gadget-foo.js'  => array( 'type' => 'script' ),
	 *        'MediaWiki:Gadget-foo.css' => array( 'type' => 'style' ),
	 * )
	 * @param $dependencies Array: Names of resources this module depends on
	 * @param $targets Array: List of targets this module support
	 * @param $position String: 'bottom' or 'top'
	 */
	public function __construct( $pages, $dependencies, $targets, $position ) {
		$this->pages = $pages;
		$this->dependencies = $dependencies;
		$this->targets = $targets;
		$this->position = $position;
		$this->isPositionDefined = true;
	}

	/**
	 * Overrides the abstract function from ResourceLoaderWikiModule class
	 * @param $context ResourceLoaderContext
	 * @return Array: $pages passed to __construct()
	 */
	protected function getPages( ResourceLoaderContext $context ) {
		return $this->pages;
	}

	/**
	 * Overrides ResourceLoaderModule::getDependencies()
	 * @param $context ResourceLoaderContext
	 * @return Array: Names of resources this module depends on
	 */
	public function getDependencies( ResourceLoaderContext $context = null ) {
		return $this->dependencies;
	}

	/**
	 * Overrides ResourceLoaderModule::getPosition()
	 * @return String: 'bottom' or 'top'
	 */
	public function getPosition() {
		return $this->position;
	}
}
