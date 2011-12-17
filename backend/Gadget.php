<?php
/**
 * Class for gadgets based on properties obtained from a JSON blob.
 * 
 * The format of the JSON blob is as follows:
 * {
 *   "settings": {
 *     // The rights required to be able to enable/load this gadget
 *     "rights": ["delete", "block"],
 *     // Whether this gadget is enabled by default
 *     "default": true,
 *     // Whether this gadget is hidden from preferences
 *     "hidden": false,
 *     // Whether this gadget is shared
 *     "shared": true,
 *     // The key of the category this gadget belongs to
 *     // Interface message is "gadgetcategory-{category}"
 *     // If this is an empty string, the gadget is uncategorized
 *     "category": "maintenance-tools"
 *   },
 *   "module": {
 *     // Scripts and styles are pages in NS_GADGET
 *     "scripts": ["foobar.js"],
 *     "styles": ["foobar.css"],
 *     "dependencies": ["jquery.ui.tabs", "gadget.awesome"],
 *     "messages": ["foobar-welcome", "foo-bye", "recentchanges"]
 *   }
 * }
 */
class Gadget {
	/** Gadget id (string) */
	protected $id;
	
	/** Gadget repository this gadget came from (GadgetRepo object) */
	protected $repo;
	
	/** Last modification timestamp of the gadget metadata (TS_MW timestamp) **/
	protected $timestamp;
	
	/** array of gadget settings, see "settings" key in the JSON blob */
	protected $settings;
	
	/** array of module settings, see "module" key in the JSON blob */
	protected $moduleData;
	
	/**
	 * Validation metadata.
	 * 'foo.bar.baz' => array( 'type check callback', 'type name' [, 'member type check callback', 'member type name'] )
	 */
	protected static $propertyValidation = array(
		'settings' => array( 'is_array', 'array' ),
		'settings.rights' => array( 'is_array', 'array' , 'is_string', 'string' ),
		'settings.default' => array( 'is_bool', 'boolean' ),
		'settings.hidden' => array( 'is_bool', 'boolean' ),
		'settings.shared' => array( 'is_bool', 'boolean' ),
		'settings.category' => array( 'is_string', 'string' ),
		'module' => array( 'is_array', 'array' ),
		'module.scripts' => array( 'is_array', 'array', 'is_string', 'string' ),
		'module.styles' => array( 'is_array', 'array', 'is_string', 'string' ),
		'module.dependencies' => array( 'is_array', 'array', 'is_string', 'string' ),
		'module.messages' => array( 'is_array', 'array', 'is_string', 'string' ),
	);
	
	/*** Public static methods ***/
	
	// Would like to do const PROPERTIES_BASE = array( ... ); here, but:
	// Fatal error: Arrays are not allowed in class constants
	// public static final $propertiesBase also doesn't work, so:
	/**
	 * Get the array representation of an empty gadget.
	 * This would have been a constant or something if PHP hadn't sucked
	 */
	public static function getPropertiesBase() {
		return array(
			'settings' => array(
				'rights' => array(),
				'default' => false,
				'hidden' => false,
				'shared' => false,
				'category' => '',
			),
			'module' => array(
				'scripts' => array(),
				'styles' => array(),
				'dependencies' => array(),
				'messages' => array(),
			),
		);
	}
	
	/**
	 * Check the validity of the given properties array
	 * @param $properties Return value of FormatJson::decode( $blob, true )
	 * @return Status object with error message if applicable
	 */
	public static function validatePropertiesArray( $properties ) {
		if ( !is_array( $properties ) ) {
			return Status::newFatal( 'gadgets-validate-invalidjson' );
		}
		foreach ( self::$propertyValidation as $property => $validation ) {
			$path = explode( '.', $property );
			$val = $properties;

			// Walk down and verify that the path from the root to this property exists
			foreach ( $path as $p ) {
				if ( !isset( $val[$p] ) ) {
					return Status::newFatal( 'gadgets-validate-notset', $property );
				}
				$val = $val[$p];
			}

			// Do the actual validation of this property
			$func = $validation[0];
			if ( !$func( $val ) ) {
				return Status::newFatal(
					'gadgets-validate-wrongtype',
					$property,
					$validation[1],
					gettype( $val )
				);
			}

			if ( isset( $validation[2] ) ) {
				// Descend into the array and check the type of each element
				$func = $validation[2];
				foreach ( $val as $i => $v ) {
					if ( !$func( $v ) ){
						return Status::newFatal(
							'gadgets-validate-wrongtype',
							"{$property}[{$i}]",
							$validation[3],
							gettype( $v )
						);
					}
				}
			}
		}

		return Status::newGood();
	}
	
	/*** Public methods ***/
	
	/**
	 * Constructor
	 * @param $id string Unique id of the gadget
	 * @param $repo GadgetRepo that this gadget came from
	 * @param $properties mixed Array or JSON blob (string) with settings and module info
	 * @param $timestamp string Timestamp (TS_MW) this gadget's metadata was last touched
	 * @throws MWException if $properties is invalid
	 */
	public function __construct( $id, $repo, $properties, $timestamp ) {
		if ( is_string( $properties ) ) {
			$properties = FormatJson::decode( $properties, true );
		}
		
		// Do a quick sanity check rather than full validation
		if ( !is_array( $properties ) || !isset( $properties['settings'] ) || !isset( $properties['module'] ) ) {
			throw new MWException( 'Invalid property array passed to ' . __METHOD__ );
		}
		
		$this->id = $id;
		$this->repo = $repo;
		$this->timestamp = $timestamp;
		$this->settings = $properties['settings'];
		$this->moduleData = $properties['module'];
	}
	
	/**
	 * Retrieve the metadata of this gadget, in the same format as $properties in __construct()
	 * @return array
	 */
	public function getMetadata() {
		return array( 'settings' => $this->settings, 'module' => $this->moduleData );
	}
	
	/**
	 * Retrieve the JSON representation of this gadget, in the same format as $properties in __construct().
	 * @return string JSON
	 */
	public function getJSON() {
		return FormatJson::encode( $this->getMetadata() );
	}
	
	/**
	 * Get the id of the gadget. This id must be unique within its repository and must never change.
	 * It is only used internally; the title displayed to the user is controlled by
	 * getTitleMessage() and getTitleMessageKey().
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}
	
	/**
	 * Get the name of the ResourceLoader source for this gadget's module
	 * @return string
	 */
	public function getRepo() { 
		return $this->repo;
	}
	 
	/**
	 * Get the last modification timestamp of the gadget metadata
	 * @return string TS_MW timestamp
	 */
	public function getTimestamp() {
		return $this->timestamp;
	}
	
	/**
	 * Get the key of the title message for this gadget. This is the interface message that
	 * controls the title of the gadget as shown to the user.
	 * @return string Message key
	 */
	public function getTitleMessageKey() {
		return "gadget-{$this->id}-title";
	}
	
	/**
	 * Get the title message for this gadget
	 * @param $langcode string Language code. If null, user language is used
	 * @return The title message in the given language, or the id of the gadget if the message doesn't exist
	 */
	public function getTitleMessage( $langcode = null ) {
		$msg = wfMessage( $this->getTitleMessageKey() );
		if ( $langcode !== null ) {
			$msg = $msg->inLanguage( $langcode );
		}
		if ( !$msg->exists() ) {
			// Fallback: return the id of the gadget
			global $wgLang;
			$lang = $langcode === null ? $wgLang : Language::factory( $langcode );
			return $lang->ucfirst( $this->id );
		}
		return $msg->plain();
		
	}
	
	/**
	 * Get the key of the description message for this gadget.
	 * @return string Message key
	 */
	public function getDescriptionMessageKey() {
		return "gadget-{$this->id}-desc";
	}
	
	/**
	 * Get the description message for this gadget
	 * @param $langcode string Language code. If null, user language is used
	 * @return The description message HTML in the given language, or an empty string if the message doesn't exist
	 */
	public function getDescriptionMessage( $langcode = null ) {
		$msg = wfMessage( $this->getDescriptionMessageKey() );
		if ( $langcode !== null ) {
			$msg = $msg->inLanguage( $langcode );
		}
		if ( !$msg->exists() ) {
			// Fallback: return empty string
			return '';
		}
		return $msg->parse();
	}
	
	/**
	 * Get the name of the category this gadget is in.
	 * @return string Category key or empty string if not in any category
	 */
	public function getCategory() {
		return $this->settings['category'];
	}
	
	/**
	 * Check whether this gadget is enabled by default. Even these gadgets can be disabled in the user's
	 * preferences, the preference just defaults to being on.
	 * @return bool
	 */
	public function isEnabledByDefault() {
		return (bool)$this->settings['default'];
	}
	
	/**
	 * Get the rights a user needs to be allowed to enable this gadget.
	 * @return array of rights (strings), empty if no restrictions
	 */
	public function getRequiredRights() {
		return (array)$this->settings['rights'];
	}
	
	/**
	 * Check whether this module is public. Modules that are not public cannot be enabled by users in
	 * their preferences and are only visible in the gadget manager.
	 * @return bool
	 */
	public function isHidden() {
		return (bool)$this->settings['hidden'];
	}
	
	/**
	 * Check whether this module is shared. Modules that are not shared cannot be loaded through
	 * a foreign repository.
	 * @return bool
	 */
	public function isShared() {
		return (bool)$this->settings['shared'];
	}
	
	/**
	 * Get the ResourceLoader module for this gadget, if available.
	 * @return ResourceLoaderModule object
	 */
	public function getModule() {
		// Build $pages
		$pages = array();
		foreach ( $this->moduleData['scripts'] as $script ) {
			$pages["Gadget:$script"] = array( 'type' => 'script' );
		}
		foreach ( $this->moduleData['styles'] as $style ) {
			$pages["Gadget:$style"] = array( 'type' => 'style' );
		}
		
		return new GadgetResourceLoaderModule(
			$pages,
			(array)$this->moduleData['dependencies'],
			(array)$this->moduleData['messages'],
			$this->repo->getSource(),
			$this->timestamp,
			$this->repo->getDB()
		);
	}
	
	/**
	 * Get the name of the ResourceLoader module for this gadget.
	 * @return string Module name
	 */
	public function getModuleName() {
		return "gadget.{$this->id}";
	}
	
	public function getScripts() {
		return $this->moduleData['scripts'];
	}
	
	public function getStyles() {
		return $this->moduleData['styles'];
	}
	
	public function getDependencies() {
		return $this->moduleData['dependencies'];
	}
	
	/*** Public methods ***/
	
	/**
	 * Check whether this gadget is enabled for a given user.
	 * @param $user User object
	 * @return bool
	 */
	public function isEnabledForUser( $user ) {
		$id = $this->getId();
		return (bool)$user->getOption( "gadget-$id", $this->isEnabledByDefault() );
	}
	
	/**
	 * Checks whether a given user has the required rights to use this gadget
	 *
	 * @param $user User: user to check against
	 * @return Boolean
	 */
	public function isAllowed( $user ) {
		$required = $this->getRequiredRights();
		$numRequired = count( $required );
		if ( $numRequired === 0 ) {
			// Short circuit to prevent calling $user->getRights()
			return true;
		}
		return count( array_intersect( $required, $user->getRights() ) ) == $numRequired;
	}
}
