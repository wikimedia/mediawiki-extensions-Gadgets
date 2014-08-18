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
 *     // Array of skins supported by this gadget, or true if all skins are supported (default)
 *     // The gadget will not be enabled on any unsupported skins
 *     "skins": true,
 *     // The key of the category this gadget belongs to
 *     // Interface message is "gadgetcategory-{category}"
 *     // If this is an empty string, the gadget is uncategorized
 *     "category": "maintenance-tools"
 *   },
 *   "module": {
 *     // Scripts and styles are pages in NS_GADGET
 *     "scripts": ["foobar.js"],
 *     "styles": ["foobar.css"],
 *     "dependencies": ["jquery.ui.tabs", "ext.gadget.awesome"],
 *     "messages": ["foobar-welcome", "foo-bye", "recentchanges"],
 *     // Whether this module should be loaded asynchronously after the page loads ('bottom', default)
 *     // or synchronously before the page is rendered ('top')
 *     "position": "bottom"
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
		'settings.skins' => array( array( __CLASS__, 'isArrayOrTrue' ), 'array or true', 'is_string', 'string' ),
		'settings.category' => array( 'is_string', 'string' ),
		'module' => array( 'is_array', 'array' ),
		'module.scripts' => array( 'is_array', 'array', 'is_string', 'string' ),
		'module.styles' => array( 'is_array', 'array', 'is_string', 'string' ),
		'module.dependencies' => array( 'is_array', 'array', 'is_string', 'string' ),
		'module.messages' => array( 'is_array', 'array', 'is_string', 'string' ),
		'module.position' => array( 'is_string', 'string' ),
	);

	/*** Public static methods ***/

	public static function isArrayOrTrue( $value ) {
		return is_array( $value ) || $value === true;
	}

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
				'skins' => true,
				'category' => '',
			),
			'module' => array(
				'scripts' => array(),
				'styles' => array(),
				'dependencies' => array(),
				'messages' => array(),
				'position' => 'bottom',
			),
		);
	}

	/**
	 * Check the validity of the given properties array
	 * @param $properties array Return value of FormatJson::decode( $blob, true )
	 * @param $tolerateMissing bool If true, don't complain about missing keys
	 * @return Status object with error message if applicable
	 */
	public static function validatePropertiesArray( $properties, $tolerateMissing = false ) {
		if ( !is_array( $properties ) ) {
			return Status::newFatal( 'gadgets-validate-invalidjson' );
		}
		foreach ( self::$propertyValidation as $property => $validation ) {
			$path = explode( '.', $property );
			$val = $properties;

			// Walk down and verify that the path from the root to this property exists
			foreach ( $path as $p ) {
				if ( !array_key_exists( $p, $val ) ) {
					if ( $tolerateMissing ) {
						// Skip validation of this property altogether
						continue 2;
					} else {
						return Status::newFatal( 'gadgets-validate-notset', $property );
					}
				}
				$val = $val[$p];
			}

			// Do the actual validation of this property
			$func = $validation[0];
			if ( !call_user_func( $func, $val ) ) {
				return Status::newFatal(
					'gadgets-validate-wrongtype',
					$property,
					$validation[1],
					gettype( $val )
				);
			}

			if ( isset( $validation[2] ) && is_array( $val ) ) {
				// Descend into the array and check the type of each element
				$func = $validation[2];
				foreach ( $val as $i => $v ) {
					if ( !call_user_func( $func, $v ) ) {
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

	/**
	 * Check a gadget ID for validity
	 *
	 * A valid gadget ID must have a valid gadget definition title (Gadget definition:$id)
	 * and a valid module name (ext.gadget.$id) associated with it, and be at most 255 bytes
	 *
	 * @param $id string Gadget ID to check
	 * @return bool Whether $id is a valid gadget ID
	 */
	public static function isValidGadgetID( $id ) {
		// Try to construct a title for the gadget definition page
		$title = Title::makeTitleSafe( NS_GADGET_DEFINITION, $id );

		return
			// Must be a valid title
			$title &&
			// Must be a valid module name
			ResourceLoader::isValidModuleName( "ext.gadget.$id" ) &&
			// Must not be the empty string
			strlen( $id )> 0 &&
			// Must fit in gd_id (255 bytes)
			// This SHOULD already be covered by the title and the module name checks,
			// but we're double-checking it here for paranoia since gadgets has its own
			// database table.
			strlen( $id ) <= 255;
	}

	/*** Protected static methods ***/

	/**
	 * Normalize a settings array. Removes duplicates and sorts the array.
	 * @param $array Array of strings
	 * @return Sorted array with duplicates removed
	 */
	protected static function normalizeArray( &$array ) {
		$array = array_unique( $array );
		natsort( $array );
		$array = array_values( $array );
	}

	/*** Public methods ***/

	/**
	 * Constructor
	 * @param $id string Unique id of the gadget
	 * @param $repo GadgetRepo that this gadget came from
	 * @throws MWException if $id or $properties is invalid
	 */
	public function __construct( $id, $repo ) {
		if ( !self::isValidGadgetID( $id ) ) {
			throw new MWException( 'Invalid gadget ID: ' . $id );
		}

		$this->id = $id;
		$this->repo = $repo;
		$this->timestamp = wfTimestampNow(); // placeholder
	}

	public function setSettings( array $settings ) {
		$base = self::getPropertiesBase();
		$this->settings = $settings + $base['settings'];
		self::normalizeArray( $this->settings['rights'] );
		if ( is_array( $this->settings['skins'] ) ) {
			self::normalizeArray( $this->settings['skins'] );
		}
	}

	public function setModuleData( array $moduleData ) {
		$base = self::getPropertiesBase();
		$this->moduleData = $moduleData + $base['module'];
		self::normalizeArray( $this->moduleData['dependencies'] );
		self::normalizeArray( $this->moduleData['messages'] );
	}

	public function setTimestamp( $timestamp ) {
		$this->timestamp = $timestamp;
	}

	public function save( $timestamp = null ) {
		if ( $timestamp === null ) {
			$timestamp = wfTimestampNow();
		}
		$this->repo->modifyGadget( $this, $timestamp );
		$this->timestamp = $timestamp;
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
	 * Retrieve the JSON representation of this gadget with pretty indentation
	 * @see getJSON
	 * @return string JSON with indentation
	 */
	public function getPrettyJSON() {
		return FormatJson::encode( $this->getMetadata(), true );
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
	 * Get the GadgetRepo this Gadget belongs to
	 * @return GadgetRepo
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
	 * Whether this gadget is a local one
	 *
	 * @return bool
	 */
	public function isLocal() {
		return get_class( $this->getRepo() ) === 'LocalGadgetRepo';
	}

	/**
	 * Get the name of the category this gadget is in.
	 * @return string Category key or empty string if not in any category
	 */
	public function getCategory() {
		return $this->settings['category'];
	}

	/**
	 * Get the skins this Gadget supports
	 * @return array|bool Array of supported skins, or true if all skins are supported
	 */
	public function getSkins() {
		return $this->settings['skins'];
	}

	/**
	 * Check whether this Gadget supports a given skin
	 * @param $skin string Skin name
	 * @return bool Whether this Gadget supports $skin
	 */
	public function supportsSkin( $skin ) {
		if ( $this->settings['skins'] === true ) {
			return true;
		} else {
			return in_array( $skin, $this->settings['skins'] );
		}
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
	 * Returns a ResourceLoaderWikiModule-style array of pages
	 *
	 * @return array
	 */
	public function getPages() {
		$pages = array();
		foreach ( $this->moduleData['scripts'] as $script ) {
			$pages["Gadget:$script"] = array( 'type' => 'script' );
		}
		foreach ( $this->moduleData['styles'] as $style ) {
			$pages["Gadget:$style"] = array( 'type' => 'style' );
		}

		return $pages;
	}

	/**
	 * Get the ResourceLoader module for this gadget, if available.
	 *
	 * This method really shouldn't be called unless there's a
	 * good reason for it.
	 *
	 * @return GadgetResourceLoaderModule
	 */
	public function getModule() {
		return new GadgetResourceLoaderModule(
			array( 'gadget' => $this )
		);
	}

	/**
	 * Get the name of the ResourceLoader module for this gadget.
	 * @return string Module name
	 */
	public function getModuleName() {
		return "ext.gadget.{$this->id}";
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

	public function getMessages() {
		return $this->moduleData['messages'];
	}

	public function getPosition() {
		return $this->moduleData['position'];
	}

	/*** Public methods ***/

	/**
	 * Check whether this gadget is enabled for a given user.
	 * @param $user User object
	 * @return bool
	 */
	public function isEnabledForUser( User $user ) {
		$id = $this->getId();
		return (bool)$user->getOption( "gadget-$id", $this->isEnabledByDefault() );
	}

	/**
	 * Checks whether a given user has the required rights to use this gadget
	 *
	 * @param $user User: user to check against
	 * @return Boolean
	 */
	public function isAllowed( User $user ) {
		$required = $this->getRequiredRights();
		$numRequired = count( $required );
		if ( $numRequired === 0 ) {
			// Short circuit
			return true;
		}
		return call_user_func_array( array( $user, 'isAllowedAll' ), $required );
	}
}
