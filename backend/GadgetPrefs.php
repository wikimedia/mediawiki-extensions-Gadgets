<?php

/**
 * Static methods for gadget preferences parsing, validation and so on.
 * 
 * @author Salvatore Ingala
 * @license GNU General Public Licence 2.0 or later
 * 
 */

class GadgetPrefs {
	
	/*
	 * Syntax specifications of preference description language.
	 * Each element describes a field; a "simple" field encodes exactly one gadget preference, but some fields
	 * may encode for 0 or multiple gadget preferences.
	 * Each field has a 'description' and may have a 'validator', a 'flattener', and a 'checker'.
	 * - 'description' is an array that describes all the members of that fields. Each member description has this shape:
	 *     - 'isMandatory' is a boolean that specifies if that member is mandatory for the field;
	 *     - 'validator', if specified, is the name of a function that validates that member
	 * - 'validator' is an optional function that does validation of the entire field description,
	 *   when member validators does not suffice since more complex semantics are needed.
	 * - 'flattener' is an optional function that takes a valid field description and returns an array of specification of
	 *   gadget preferences, with preference names as keys and corresponding "simple" field descriptions as values.
	 *   If omitted (for "simple fields"), the default flattener is used.
	 * - 'checker', only for "simple" fields, is the name of a function that takes a preference description and
	 *   a preference value, and returns true if that value passes validation, false otherwise.
	 * - 'getMessages', if specified, is the name of a function that takes a valid description of a field and returns
	 *   a list of messages referred to by it. If omitted, only the "label" field is returned (if it is a message).
	 */
	private static $prefsDescriptionSpecifications = array(
		'boolean' => array( 
			'description' => array(
				'name' => array(
					'isMandatory' => true,
					'validator' => 'GadgetPrefs::isValidPreferenceName'
				),
				'default' => array(
					'isMandatory' => true,
					'validator' => 'is_bool'
				),
				'label' => array(
					'isMandatory' => true,
					'validator' => 'is_string'
				)
			),
			'checker' => 'GadgetPrefs::checkBooleanPref'
		),
		'string' => array(
			'description' => array(
				'name' => array(
					'isMandatory' => true,
					'validator' => 'GadgetPrefs::isValidPreferenceName'
				),
				'default' => array(
					'isMandatory' => true,
					'validator' => 'is_string'
				),
				'label' => array(
					'isMandatory' => true,
					'validator' => 'is_string'
				),
				'required' => array(
					'isMandatory' => false,
					'validator' => 'is_bool'
				),
				'minlength' => array(
					'isMandatory' => false,
					'validator' => 'is_integer'
				),
				'maxlength' => array(
					'isMandatory' => false,
					'validator' => 'is_integer'
				)
			),
			'validator' => 'GadgetPrefs::validateStringOptionDefinition',
			'checker' => 'GadgetPrefs::checkStringPref'
		),
		'number' => array(
			'description' => array(
				'name' => array(
					'isMandatory' => true,
					'validator' => 'GadgetPrefs::isValidPreferenceName'
				),
				'default' => array(
					'isMandatory' => true,
					'validator' => 'GadgetPrefs::isFloatOrIntOrNull'
				),
				'label' => array(
					'isMandatory' => true,
					'validator' => 'is_string'
				),
				'required' => array(
					'isMandatory' => false,
					'validator' => 'is_bool'
				),
				'integer' => array(
					'isMandatory' => false,
					'validator' => 'is_bool'
				),
				'min' => array(
					'isMandatory' => false,
					'validator' => 'GadgetPrefs::isFloatOrInt'
				),
				'max' => array(
					'isMandatory' => false,
					'validator' => 'GadgetPrefs::isFloatOrInt'
				)
			),
			'validator' => 'GadgetPrefs::validateNumberOptionDefinition',
			'checker' => 'GadgetPrefs::checkNumberPref'
		),
		'select' => array(
			'description' => array(
				'name' => array(
					'isMandatory' => true,
					'validator' => 'GadgetPrefs::isValidPreferenceName'
				),
				'default' => array(
					'isMandatory' => true
				),
				'label' => array(
					'isMandatory' => true,
					'validator' => 'is_string'
				),
				'options' => array(
					'isMandatory' => true,
					'validator' => 'is_array'
				)
			),
			'validator' => 'GadgetPrefs::validateSelectOptionDefinition',
			'checker' => 'GadgetPrefs::checkSelectPref',
			'getMessages' => 'GadgetPrefs::getSelectMessages'
		),
		'range' => array(
			'description' => array(
				'name' => array(
					'isMandatory' => true,
					'validator' => 'GadgetPrefs::isValidPreferenceName'
				),
				'default' => array(
					'isMandatory' => true,
					'validator' => 'GadgetPrefs::isFloatOrIntOrNull'
				),
				'label' => array(
					'isMandatory' => true,
					'validator' => 'is_string'
				),
				'min' => array(
					'isMandatory' => true,
					'validator' => 'GadgetPrefs::isFloatOrInt'
				),
				'max' => array(
					'isMandatory' => true,
					'validator' => 'GadgetPrefs::isFloatOrInt'
				),
				'step' => array(
					'isMandatory' => false,
					'validator' => 'GadgetPrefs::isFloatOrInt'
				)
			),
			'validator' => 'GadgetPrefs::validateRangeOptionDefinition',
			'checker' => 'GadgetPrefs::checkRangePref'
		),
		'date' => array(
			'description' => array(
				'name' => array(
					'isMandatory' => true,
					'validator' => 'GadgetPrefs::isValidPreferenceName'
				),
				'default' => array(
					'isMandatory' => true
				),
				'label' => array(
					'isMandatory' => true,
					'validator' => 'is_string'
				)
			),
			'checker' => 'GadgetPrefs::checkDatePref'
		),
		'color' => array(
			'description' => array(
				'name' => array(
					'isMandatory' => true,
					'validator' => 'GadgetPrefs::isValidPreferenceName'
				),
				'default' => array(
					'isMandatory' => true
				),
				'label' => array(
					'isMandatory' => true,
					'validator' => 'is_string'
				)
			),
			'checker' => 'GadgetPrefs::checkColorPref'
		)
	);
	
	private static function isValidPreferenceName( $name ) {
		return strlen( $name ) <= 40
				&& preg_match( '/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name );
	}

	
	//Further checks for 'string' options
	private static function validateStringOptionDefinition( $option ) {
		if ( isset( $option['minlength'] ) && $option['minlength'] < 0 ) {
			return false;
		}

		if ( isset( $option['maxlength'] ) && $option['maxlength'] <= 0 ) {
			return false;
		}

		if ( isset( $option['minlength']) && isset( $option['maxlength'] ) ) {
			if ( $option['minlength'] > $option['maxlength'] ) {
				return false;
			}
		}
		
		return true;
	}

	private static function isFloatOrInt( $param ) {
		return is_float( $param ) || is_int( $param );
	}

	private static function isFloatOrIntOrNull( $param ) {
		return is_float( $param ) || is_int( $param ) || $param === null;
	}
	
	//default flattener for simple fields that encode for a single preference
	private static function flattenSimpleField( $fieldDescription ) {
		return array( $fieldDescription['name'] => $fieldDescription );
	}
	
	//Further checks for 'number' options
	private static function validateNumberOptionDefinition( $option ) {
		if ( isset( $option['integer'] ) && $option['integer'] === true ) {
			//Check if 'min', 'max' and 'default' are integers (if given)
			if ( intval( $option['default'] ) != $option['default'] ) {
				return false;
			}
			if ( isset( $option['min'] ) && intval( $option['min'] ) != $option['min'] ) {
				return false;
			}
			if ( isset( $option['max'] ) && intval( $option['max'] ) != $option['max'] ) {
				return false;
			}
		}

		return true;
	}

	private static function validateSelectOptionDefinition( $option ) {
		$options = $option['options'];
		
		foreach ( $options as $opt => $optVal ) {
			//Correct value for $optVal are NULL, boolean, integer, float or string
			if ( $optVal !== NULL &&
				!is_bool( $optVal ) &&
				!is_int( $optVal ) &&
				!is_float( $optVal ) &&
				!is_string( $optVal ) )
			{
				return false;
			}
		}
		
		$values = array_values( $options );
		
		return true;
	}
	
	private static function validateRangeOptionDefinition( $option ) {
		$step = isset( $option['step'] ) ? $option['step'] : 1;
		
		if ( $step <= 0 ) {
			return false;
		}
		
		$min = $option['min'];
		$max = $option['max'];
		
		//Checks if 'max' is a valid value
		//Valid values are min, min + step, min + 2*step, ...
		//Then ( $max - $min ) / $step must be close enough to an integer
		$eps = 1.0e-6; //tolerance
		$tmp = ( $max - $min ) / $step;
		if ( abs( $tmp - floor( $tmp ) ) > $eps ) {
			return false;
		}
		
		return true;
	}

	//Flattens a simple field, by calling its field-specific flattener if there is any,
	//or the default flattener otherwise.
	private static function flattenFieldDescription( $fieldDescription ) {
		$typeSpec = self::$prefsDescriptionSpecifications[$fieldDescription['type']];
		$typeDescription = $typeSpec['description'];
		if ( isset( $typeSpec['flattener'] ) ) {
			$flattener = $typeSpec['flattener'];
		} else {
			$flattener = 'GadgetPrefs::flattenSimpleField';
		}
		return call_user_func( $flattener, $fieldDescription );
	}

	//Returns a map keyed at preference names, and with their corresponding
	//"simple" field descriptions as values.
	//It is assumed that $prefsDescription is valid.
	private static function flattenPrefsDescription( $prefsDescription ) {
		$flattenedPrefsDescription = array();
		foreach ( $prefsDescription['fields'] as $fieldDescription ) {
			$flt = self::flattenFieldDescription( $fieldDescription );
			$flattenedPrefsDescription = array_merge( $flattenedPrefsDescription, $flt );
		}
		return $flattenedPrefsDescription;
	}

	//Validate the description of a 'section' of preferences 
	private static function validateSectionDefinition( $sectionDescription ) {
		static $mandatoryCount = array(), $initialized = false;

		if ( !is_array( $sectionDescription )
			|| !isset( $sectionDescription['fields'] )
			|| !is_array( $sectionDescription['fields'] ) )
		{
			return false;
		}
		
		if ( !$initialized ) {
			//Count of mandatory members for each type
			foreach ( self::$prefsDescriptionSpecifications as $type => $typeSpec ) {
				$mandatoryCount[$type] = 0;
				foreach ( $typeSpec['description'] as $fieldName => $fieldSpec ) {
					if ( $fieldSpec['isMandatory'] === true ) {
						++$mandatoryCount[$type];
					}
				}
			}
			$initialized = true;
		}
		
		//Check if 'fields' is a regular (not-associative) array, and that it is not empty
		$count = count( $sectionDescription['fields'] );
		if ( $count == 0 || array_keys( $sectionDescription['fields'] ) !== range( 0, $count - 1 ) ) {
			return false;
		}
		
		//TODO: validation of members other than $prefs['fields']

		//Flattened preferences
		$flattenedPrefs = array();
		
		foreach ( $sectionDescription['fields'] as $optionDefinition ) {
			
			//Check if 'type' is set
			if ( !isset( $optionDefinition['type'] ) )  {
				return false;
			}
			
			$type = $optionDefinition['type'];
			
			//check if 'type' is valid
			if ( !isset( self::$prefsDescriptionSpecifications[$type] ) ) {
				return false;
			}
			
			//Check if all fields satisfy specification
			$typeSpec = self::$prefsDescriptionSpecifications[$type];
			$typeDescription = $typeSpec['description'];
			$count = 0; //count of present mandatory members
			foreach ( $optionDefinition as $fieldName => $fieldValue ) {
				
				if ( $fieldName == 'type' ) {
					continue; //'type' must not be checked
				}
				
				if ( !isset( $typeDescription[$fieldName] ) ) {
					return false;
				}
				
				if ( $typeDescription[$fieldName]['isMandatory'] ) {
					++$count;
				}
				
				if ( isset( $typeDescription[$fieldName]['validator'] ) ) {
					$validator = $typeDescription[$fieldName]['validator'];
					if ( !call_user_func( $validator, $fieldValue ) ) {
						return false;
					}
				}
			}
			
			if ( $count != $mandatoryCount[$type] ) {
				return false; //not all mandatory members are given
			}
			
			if ( isset( $typeSpec['validator'] ) ) {
				//Call type-specific checker for finer validation
				if ( !call_user_func( $typeSpec['validator'], $optionDefinition ) ) {
					return false;
				}
			}

			//flatten preferences described by this field
			$flt = self::flattenFieldDescription( $optionDefinition );
			
			foreach ( $flt as $prefName => $prefDescription ) {
				//Finally, check that the 'default' fields exists and is valid
				//for all preferences encoded by this field
				if ( !array_key_exists( 'default', $prefDescription ) ) {
					return false;
				}
				
				$prefs = array( 'dummy' => $optionDefinition['default'] );
				if ( !self::checkSinglePref( $optionDefinition, $prefs, 'dummy' ) ) {
					return false;
				}
			}
			
			//If there are preferences with the same name of a previously encountered preference, fail
			if ( array_intersect( array_keys( $flt ), array_keys( $flattenedPrefs ) ) ) {
				return false;
			}
			$flattenedPrefs = array_merge( $flattenedPrefs, $flt );
		}
		
		return true;
	}
	
	//Checks if the given description of the preferences is valid
	public static function isPrefsDescriptionValid( $prefsDescription ) {
		return self::validateSectionDefinition( $prefsDescription );
	}
	
	//Check if a preference is valid, according to description.
	//$prefDescription must be the description of a "simple" field (that is, with 'checker')
	//NOTE: we pass both $prefs and $prefName (instead of just $prefs[$prefName])
	//      to allow checking for undefined values.
	private static function checkSinglePref( $prefDescription, $prefs, $prefName ) {

		//isset( $prefs[$prefName] ) would return false for null values
		if ( !array_key_exists( $prefName, $prefs ) ) {
			return false;
		}
	
		$value = $prefs[$prefName];
		$type = $prefDescription['type'];
		
		if ( !isset( self::$prefsDescriptionSpecifications[$type] )
			|| !isset( self::$prefsDescriptionSpecifications[$type]['checker'] ) )
		{
			return false;
		}
		
		$checker = self::$prefsDescriptionSpecifications[$type]['checker'];
		return call_user_func( $checker, $prefDescription, $value );
	}

	//Checker for 'boolean' preferences
	private static function checkBooleanPref( $prefDescription, $value ) {
		return is_bool( $value );
	}

	//Checker for 'string' preferences
	private static function checkStringPref( $prefDescription, $value ) {
		if ( !is_string( $value ) ) {
			return false;
		}
		
		$len = strlen( $value );
		
		//Checks the "required" option, if present
		$required = isset( $prefDescription['required'] ) ? $prefDescription['required'] : true;
		if ( $required === true && $len == 0 ) {
			return false;
		} elseif ( $required === false && $len == 0 ) {
			return true; //overriding 'minlength'
		}
		
		//Checks the "minlength" option, if present
		$minlength = isset( $prefDescription['minlength'] ) ? $prefDescription['minlength'] : 0;
		if ( $len < $minlength ){
			return false;
		}

		//Checks the "maxlength" option, if present
		$maxlength = isset( $prefDescription['maxlength'] ) ? $prefDescription['maxlength'] : 1024; //TODO: what big integer here?
		if ( $len > $maxlength ){
			return false;
		}
		
		return true;
	}

	//Checker for 'number' preferences
	private static function checkNumberPref( $prefDescription, $value ) {
		if ( !is_float( $value ) && !is_int( $value ) && $value !== null ) {
			return false;
		}

		$required = isset( $prefDescription['required'] ) ? $prefDescription['required'] : true;
		if ( $required === false && $value === null ) {
			return true;
		}
		
		if ( $value === null ) {
			return false; //$required === true, so null is not acceptable
		}

		$integer = isset( $prefDescription['integer'] ) ? $prefDescription['integer'] : false;
		
		if ( $integer === true && intval( $value ) != $value ) {
			return false; //not integer
		}
		
		if ( isset( $prefDescription['min'] ) ) {
			$min = $prefDescription['min'];
			if ( $value < $min ) {
				return false; //value below minimum
			}
		}

		if ( isset( $prefDescription['max'] ) ) {
			$max = $prefDescription['max'];
			if ( $value > $max ) {
				return false; //value above maximum
			}
		}

		return true;
	}

	//Checker for 'select' preferences
	private static function checkSelectPref( $prefDescription, $value ) {
		$values = array_values( $prefDescription['options'] );
		return in_array( $value, $values, true );
	}

	//Checker for 'range' preferences
	private static function checkRangePref( $prefDescription, $value ) {
		if ( !is_float( $value ) && !is_int( $value ) ) {
			return false;
		}
		
		$min = $prefDescription['min'];
		$max = $prefDescription['max'];
		
		if ( $value < $min || $value > $max ) {
			return false;
		}
		
		$step = isset( $prefDescription['step'] ) ? $prefDescription['step'] : 1;
		
		if ( $step <= 0 ) {
			return false;
		}
		
		//Valid values are min, min + step, min + 2*step, ...
		//Then ( $value - $min ) / $step must be close enough to an integer
		$eps = 1.0e-6; //tolerance
		$tmp = ( $value - $min ) / $step;
		if ( abs( $tmp - floor( $tmp ) ) > $eps ) {
			return false;
		}
		
		return true;
	}

	//Checker for 'date' preferences
	private static function checkDatePref( $prefDescription, $value ) {
		if ( $value === null ) {
			return true;
		}
		
		//Basic syntactic checks
		if ( !is_string( $value ) ||
			!preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value ) )
		{
			return false;
		}
		
		//Full parsing
		return date_create( $value ) !== false;
	}

	//Checker for 'color' preferences
	private static function checkColorPref( $prefDescription, $value ) {
		//Check if it's a string representing a color
		//(with 6 hexadecimal lowercase characters).
		return is_string( $value ) && preg_match( '/^#[0-9a-f]{6}$/', $value );
	}



	/**
	 * Checks if $prefs is an array of preferences that passes validation.
	 * It is assumed that $prefsDescription is a valid description of preferences.
	 * 
	 * @param $prefsDescription Array: the preferences description to use.
	 * @param $prefs Array: reference of the array of preferences to check.
	 * 
	 * @return boolean true if $prefs passes validation against $prefsDescription, false otherwise.
	 */
	public static function checkPrefsAgainstDescription( $prefsDescription, $prefs ) {
		$flattenedPrefs = self::flattenPrefsDescription( $prefsDescription );
		$validPrefs = array();
		//Check that all the given preferences pass validation
		foreach ( $flattenedPrefs as $prefDescription ) {
			$prefName = $prefDescription['name'];
			if ( !self::checkSinglePref( $prefDescription, $prefs, $prefName ) ) {
				return false;
			}
			$validPrefs[$prefName] = true;
		}
		
		//Check that $prefs contains no preferences that are not described in $prefsDescription
		foreach ( $prefs as $prefName => $value ) {
			if ( !isset( $validPrefs[$prefName] ) ) {
				return false;
			}
		}
		
		return true;
	}

	/**
	 * Fixes $prefs so that it matches the description given by $prefsDescription.
	 * All values of $prefs that fail validation are replaced with default values.
	 * It is assumed that $prefsDescription is a valid description of preferences.
	 * 
	 * @param $prefsDescription Array: the preferences description to use.
	 * @param &$prefs Array: reference of the array of preferences to match.
	 */
	public static function matchPrefsWithDescription( $prefsDescription, &$prefs ) {
		$flattenedPrefs = self::flattenPrefsDescription( $prefsDescription );
		$validPrefs = array();
		
		//Fix preferences that fail validation, by replacing their value with default
		foreach ( $flattenedPrefs as $prefDescription ) {
			$prefName = $prefDescription['name'];
			if ( !self::checkSinglePref( $prefDescription, $prefs, $prefName ) ) {
				$prefs[$prefName] = $prefDescription['default'];
			}
			$validPrefs[$prefName] = true;
		}
		
		//Remove unexisting preferences from $prefs
		foreach ( $prefs as $prefName => $value ) {
			if ( !isset( $validPrefs[$prefName] ) ) {
				unset( $prefs[$prefName] );
			}
		}
	}
	
	/**
	 * Return default preferences according to the given description.
	 * 
	 * @param $prefsDescription Array: reference of the array of preferences to match.
	 * It is assumed that $prefsDescription is a valid description of preferences.
	 * 
	 * @return Array: the set of default preferences, keyed by preference name.
	 */
	public static function getDefaults( $prefsDescription ) {
		$prefs = array();
		self::matchPrefsWithDescription( $prefsDescription, $prefs );
		return $prefs;
	}
	
	/**
	 * Returns true if $str should be interpreted as a message, false otherwise.
	 * 
	 * @param $str String
	 * @return Mixed
	 * 
	 */
	private static function isMessage( $str ) {
		return strlen( $str ) >= 2
			&& $str[0] == '@'
			&& $str[1] != '@';
	}
	
	/**
	 * Returns a list of (unprefixed) messages mentioned by $prefsDescription. It is assumed that
	 * $prefsDescription is valid (i.e.: GadgetPrefs::isPrefsDescriptionValid( $prefsDescription ) === true).
	 * 
	 * @param $prefsDescription Array: the preferences description to use.
	 * @return Array: the messages needed by $prefsDescription.
	 */
	public static function getMessages( $prefsDescription ) {
		$msgs = array();
		
		if ( isset( $prefsDescription['intro'] ) && self::isMessage( $prefsDescription['intro'] ) ) {
			$msgs[] = substr( $prefsDescription['intro'], 1 );
		}
		
		foreach ( $prefsDescription['fields'] as $prefDesc ) {
			$type = $prefDesc['type'];
			$prefSpec = self::$prefsDescriptionSpecifications[$type];
			if ( isset( $prefSpec['getMessages'] ) ) {
				$getMessages = $prefSpec['getMessages'];
				$msgs = array_merge( $msgs, call_user_func( $getMessages, $prefDesc ) );
			} else {
				if ( isset( $prefDesc['label'] ) && self::isMessage( $prefDesc['label'] ) ) {
					$msgs[] = substr( $prefDesc['label'], 1 );
				}
			}
		}
		
		return array_unique( $msgs );
	}
	
	//Returns the messages for a 'select' field description
	private static function getSelectMessages( $prefDescription ) {
		$msgs = array();
		foreach ( $prefDescription['options'] as $optName => $value ) {
			if ( self::isMessage( $optName ) ) {
				$msgs[] = substr( $optName, 1 );
			}
		}
		return $msgs;
	}
}
