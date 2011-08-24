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
	 * "Simple" field always have the 'name' member. Not "simple" fields never do.
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
	 * - 'matcher', only for "simple" fields, is the name of a function that takes the description of a field $prefDescription,
	 *   an array of preference values $prefs and the name of a preference $preferenceName and returns an array where
	 *   $prefs[$prefName] is changed in a way that passes validation. If omitted, the default action is to set $prefs[$prefName]
	 *   to $prefDescription['default'].
	 * - 'simplifier', only for "simple" fields, is an optional function that takes two arguments, a valid description
	 *   of a field $prefDescription, and an array of preference values $prefs; it returns an array where the preference
	 *   encoded by $prefDescription is removed if it is equal to default. If omitted, the preference is omitted if it
	 *   equals $prefDescription['default'].
	 * - 'getDefault', only for "simple" fields, if a function che takes one argument, the description of the field, and
	 *   returns its default value; if omitted, the value of the 'default' field is returned.
	 * - 'getMessages', if specified, is the name of a function that takes a valid description of a field and returns
	 *   a list of messages referred to by it. If omitted, only the "label" field is returned (if it is a message).
	 */
	private static $prefsDescriptionSpecifications = array(
		'label' => array(
			'description' => array(
				'label' => array(
					'isMandatory' => true,
					'validator' => 'is_string'
				)
			),
			'flattener' => 'GadgetPrefs::flattenLabelDefinition'
		),
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
			'validator' => 'GadgetPrefs::validateStringPrefDefinition',
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
			'validator' => 'GadgetPrefs::validateNumberPrefDefinition',
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
					'validator' => 'GadgetPrefs::isOrdinaryArray'
				)
			),
			'validator' => 'GadgetPrefs::validateSelectPrefDefinition',
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
			'validator' => 'GadgetPrefs::validateRangePrefDefinition',
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
		),
		'bundle' => array(
			'description' => array(
				'sections' => array(
					'isMandatory' => true,
					'checker' => 'GadgetPrefs::validateBundleSectionsDefinition'
				)
			),
			'getMessages' => 'GadgetPrefs::getBundleMessages',
			'flattener' => 'GadgetPrefs::flattenBundleDefinition'
		),
		'composite' => array(
			'description' => array(
				'name' => array(
					'isMandatory' => true,
					'validator' => 'GadgetPrefs::isValidPreferenceName'
				),
				'fields' => array(
					'isMandatory' => true,
					'validator' => 'is_array'
				)
			),
			'validator' => 'GadgetPrefs::validateSectionDefinition',
			'getMessages' => 'GadgetPrefs::getCompositeMessages',
			'getDefault' => 'GadgetPrefs::getCompositeDefault',
			'checker' => 'GadgetPrefs::checkCompositePref',
			'matcher' => 'GadgetPrefs::matchCompositePref',
			'simplifier' => 'GadgetPrefs::simplifyCompositePref'
		),
		'list' => array(
			'description' => array(
				'name' => array(
					'isMandatory' => true,
					'validator' => 'GadgetPrefs::isValidPreferenceName'
				),
				'field' => array(
					'isMandatory' => true,
					'validator' => 'is_array'
				),
				'default' => array(
					'isMandatory' => true
				)
			),
			'validator' => 'GadgetPrefs::validateListPrefDefinition',
			'getMessages' => 'GadgetPrefs::getListMessages',
			'checker' => 'GadgetPrefs::checkListPref',
			'matcher' => 'GadgetPrefs::matchListPref'
		)		
	);
	
	private static function isValidPreferenceName( $name ) {
		return strlen( $name ) <= 40
				&& preg_match( '/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name );
	}

	
	//Further checks for 'string' options
	private static function validateStringPrefDefinition( $prefDefinition ) {
		if ( isset( $prefDefinition['minlength'] ) && $prefDefinition['minlength'] < 0 ) {
			return false;
		}

		if ( isset( $prefDefinition['maxlength'] ) && $prefDefinition['maxlength'] <= 0 ) {
			return false;
		}

		if ( isset( $prefDefinition['minlength']) && isset( $prefDefinition['maxlength'] ) ) {
			if ( $prefDefinition['minlength'] > $prefDefinition['maxlength'] ) {
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
	
	//Checks if $param is an ordinary (i.e.: not associative) array
	private static function isOrdinaryArray( $param ) {
		if ( !is_array( $param ) ) {
			return false;
		}

		$count = count( $param );
		return $count == 0 || array_keys( $param ) === range( 0, $count - 1 );
	}
	
	private static function flattenLabelDefinition( $fieldDescription ) {
		return array();
	}
	
	//default flattener for simple fields that encode for a single preference
	private static function flattenSimpleFieldDefinition( $fieldDescription ) {
		return array( $fieldDescription['name'] => $fieldDescription );
	}
	
	//flattener for 'bundle' fields
	private static function flattenBundleDefinition( $fieldDescription ) {
		$flattenedPrefs = array();
		foreach ( $fieldDescription['sections'] as $sectionDescription ) {
			//Each section behaves like a full description of preferences
			$flt = self::flattenPrefsDescription( $sectionDescription );
			$flattenedPrefs = array_merge( $flattenedPrefs, $flt );
		}
		return $flattenedPrefs;
	}
	
	//Further checks for 'number' options
	private static function validateNumberPrefDefinition( $prefDefinition ) {
		if ( isset( $prefDefinition['integer'] ) && $prefDefinition['integer'] === true ) {
			//Check if 'min', 'max' and 'default' are integers (if given)
			if ( intval( $prefDefinition['default'] ) != $prefDefinition['default'] ) {
				return false;
			}
			if ( isset( $prefDefinition['min'] ) && intval( $prefDefinition['min'] ) != $prefDefinition['min'] ) {
				return false;
			}
			if ( isset( $prefDefinition['max'] ) && intval( $prefDefinition['max'] ) != $prefDefinition['max'] ) {
				return false;
			}
		}

		return true;
	}

	private static function validateSelectPrefDefinition( $prefDefinition ) {
		$options = $prefDefinition['options'];
		
		//Check if it's a regular array
		if ( !self::isOrdinaryArray( $options ) ) {
			return false;
		}
		
		foreach ( $options as $option ) {
			//Using array_key_exists() because isset fails for null values
			if ( !isset( $option['name'] ) || !array_key_exists( 'value', $option ) ) {
				return false;
			}
		
			//All names must be strings
			if ( !is_string( $option['name'] ) ) {
				return false;
			}
		
			//Correct value for $value are null, boolean, integer, float or string
			$value = $option['value'];
			if ( $value !== null &&
				!is_bool( $value ) &&
				!is_int( $value ) &&
				!is_float( $value ) &&
				!is_string( $value ) )
			{
				return false;
			}
		}

		return true;
	}
	
	private static function validateRangePrefDefinition( $prefDefinition ) {
		$step = isset( $prefDefinition['step'] ) ? $prefDefinition['step'] : 1;
		
		if ( $step <= 0 ) {
			return false;
		}
		
		$min = $prefDefinition['min'];
		$max = $prefDefinition['max'];
		
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

	private static function validateListPrefDefinition( $prefDefinition ) {
		//Name must not be set for the 'field' description
		if ( array_key_exists( 'name', $prefDefinition['field'] ) ) {
			return false;
		}
		
		//Check if the field definition is valid, apart from missing the name
		$itemDescription = $prefDefinition['field'];
		$itemDescription['name'] = 'dummy';
		if ( !self::validateFieldDefinition( $itemDescription ) ) {
			return false;
		};
		
		//Finally, type described by the 'field' member must be a simple type (e.g.: have "name" ).
		$type = $itemDescription['type'];
		return isset( self::$prefsDescriptionSpecifications[$type]['description']['name'] );
	}

	//Flattens a simple field, by calling its field-specific flattener if there is any,
	//or the default flattener otherwise.
	private static function flattenFieldDescription( $fieldDescription ) {
		$fieldSpec = self::$prefsDescriptionSpecifications[$fieldDescription['type']];
		if ( isset( $fieldSpec['flattener'] ) ) {
			$flattener = $fieldSpec['flattener'];
		} else {
			$flattener = 'GadgetPrefs::flattenSimpleFieldDefinition';
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

	//Validates a single field
	private static function validateFieldDefinition( $fieldDefinition ) {
		static $mandatoryCount = array(), $initialized = false;

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

		//Check if 'type' is set
		if ( !isset( $fieldDefinition['type'] ) )  {
			return false;
		}
		
		$type = $fieldDefinition['type'];
		
		//check if 'type' is valid
		if ( !isset( self::$prefsDescriptionSpecifications[$type] ) ) {
			return false;
		}
		
		//Check if all fields satisfy specification
		$typeSpec = self::$prefsDescriptionSpecifications[$type];
		$typeDescription = $typeSpec['description'];
		$count = 0; //count of present mandatory members
		foreach ( $fieldDefinition as $memberName => $memberValue ) {
			
			if ( $memberName == 'type' ) {
				continue; //'type' must not be checked
			}
			
			if ( !isset( $typeDescription[$memberName] ) ) {
				return false;
			}
			
			if ( $typeDescription[$memberName]['isMandatory'] ) {
				++$count;
			}
			
			if ( isset( $typeDescription[$memberName]['validator'] ) ) {
				$validator = $typeDescription[$memberName]['validator'];
				if ( !call_user_func( $validator, $memberValue ) ) {
					return false;
				}
			}
		}
		
		if ( $count != $mandatoryCount[$type] ) {
			return false; //not all mandatory members are given
		}
		
		if ( isset( $typeSpec['validator'] ) ) {
			//Call type-specific checker for finer validation
			if ( !call_user_func( $typeSpec['validator'], $fieldDefinition ) ) {
				return false;
			}
		}
		
		return true;
	}

	//Validate the description of a 'section' of preferences 
	private static function validateSectionDefinition( $sectionDescription ) {
		if ( !is_array( $sectionDescription )
			|| !isset( $sectionDescription['fields'] )
			|| !is_array( $sectionDescription['fields'] ) )
		{
			return false;
		}
		
		//Check if 'fields' is a regular (not-associative) array, and that it is not empty
		$count = count( $sectionDescription['fields'] );
		if ( $count == 0 || array_keys( $sectionDescription['fields'] ) !== range( 0, $count - 1 ) ) {
			return false;
		}
		
		//TODO: validation of members other than $prefs['fields']

		//Flattened preferences
		$flattenedPrefs = array();
		
		foreach ( $sectionDescription['fields'] as $fieldDefinition ) {
			
			if ( self::validateFieldDefinition( $fieldDefinition ) == false ) {
				return false;
			}

			//flatten preferences described by this field
			$flt = self::flattenFieldDescription( $fieldDefinition );
			
			foreach ( $flt as $prefName => $prefDescription ) {
				//Finally, check that the 'default' fields exists and is valid
				//for all preferences encoded by this field
				
				$type = $prefDescription['type'];
				if ( isset( self::$prefsDescriptionSpecifications[$type]['getDefault'] ) ) {
					$getDefault = self::$prefsDescriptionSpecifications[$type]['getDefault'];
					$value = call_user_func( $getDefault, $prefDescription );
				} else {
					if ( !array_key_exists( 'default', $prefDescription ) ) {
						return false;
					}
					$value = $prefDescription['default'];
				}
				
				$prefs = array( $prefName => $value );
				if ( !self::checkSinglePref( $prefDescription, $prefs, $prefName ) ) {
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
	
	//validates the 'sections' member of a 'bundle' field
	private static function validateBundleSectionsDefinition( $sections ) {
		//validate each section, then ensure that preference names
		//of each section are disjoint
		
		if ( !self::isOrdinaryArray( $sections ) ) {
			return false;
		}
		
		$prefs = array(); //names of preferences
		
		foreach ( $sections as $section ) {
			if ( !self::validateSectionDefinition( $section ) ) {
				return false;
			}
			
			//Bundle sections must have a "title" field
			if ( !isset( $section['title'] ) || !is_istring( $section['title'] ) ) {
				return false;
			}
			
			$flt = self::flattenPrefsDescription( $section );
			$newPrefs = array_keys( $flt );
			if ( array_intersect( $prefs, $newPrefs ) ) {
				return false;
			}
			
			$prefs = array_merge( $prefs, $newPrefs );
		}
		
		return true;
	}
	
	/**
	 * Checks validity of a preferences description.
	 * 
	 * @param $prefsDescription Array: the preferences description to check.
	 * 
	 * @return boolean true if $prefsDescription is a valid description of preferences, false otherwise.
	 */
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
		foreach ( $prefDescription['options'] as $option ) {
			if ( $option['value'] === $value ) {
				return true;
			}
		}

		return false;
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

	//Checker for 'composite' preferences
	private static function checkCompositePref( $prefDescription, $value ) {
		if ( !is_array( $value ) ) {
			return false;
		}

		$flattened = self::flattenPrefsDescription( $prefDescription );

		foreach ( $flattened as $subPrefName => $subPrefDescription ) {
			if ( !array_key_exists( $subPrefName, $value ) ||
				!self::checkSinglePref( $subPrefDescription, $value, $subPrefName ) )
			{
				return false;
			}
		}
		return true;
	}

	//Checker for 'list' preferences
	private static function checkListPref( $prefDescription, $value ) {
		if ( !self::isOrdinaryArray( $value ) ) {
			return false;
		}

		$itemDescription = $prefDescription['field'];
		foreach ( $value as $item ) {
			if ( !self::checkSinglePref( $itemDescription, array( 'dummy' => $item ), 'dummy' ) ) {
				return false;
			}
		}

		return true;
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

	//Matcher for 'composite' type preferences
	private static function matchCompositePref( $prefDescription, $prefs, $prefName ) {
		if ( !array_key_exists( $prefName, $prefs ) || !is_array( $prefs[$prefName] ) ) {
			$prefs[$prefName] = array();
		}
		
		self::matchPrefsWithDescription( $prefDescription, $prefs[$prefName] );
		
		return $prefs;
	}

	//Matcher for 'list' type preferences
	//If value is not an array, just reset to default; otherwise, delete elements that fail validation
	private static function matchListPref( $prefDescription, $prefs, $prefName ) {
		if ( !isset( $prefs[$prefName] ) || !self::isOrdinaryArray( $prefs[$prefName] ) ) {
			$prefs[$prefName] = $prefDescription['default'];
			return $prefs;
		}
		
		$itemDescription = $prefDescription['field'];
		$newItems = array();
		foreach( $prefs[$prefName] as $item ) {
			if ( self::checkSinglePref( $itemDescription, array( 'dummy' => $item ), 'dummy' ) ) {
				$newItems[] = $item;
			}
		}
		$prefs[$prefName] = $newItems;
		
		return $prefs;
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
				$type = $prefDescription['type'];
				if ( isset( self::$prefsDescriptionSpecifications[$type]['matcher'] ) ) {
					//Use specific matcher for this type
					$matcher = self::$prefsDescriptionSpecifications[$type]['matcher'];
					$prefs = call_user_func( $matcher, $prefDescription, $prefs, $prefName );
				} else {
					//Default matcher, just use 'default' value
					$prefs[$prefName] = $prefDescription['default'];
				}
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
	 * Removes from $prefs all preferences that don't need to be saved, because
	 * they are equal to their default value.
	 * It is assumed that $prefsDescription is a valid description of preferences.
	 * 
	 * @param $prefsDescription Array: the preferences description to use.
	 * @param &$prefs Array: reference of the array of preferences to simplify.
	 */
	public static function simplifyPrefs( $prefsDescription, &$prefs ) {
		$flattenedPrefs = self::flattenPrefsDescription( $prefsDescription );
		
		foreach( $flattenedPrefs as $prefName => $prefDescription ) {
			$type = $prefDescription['type'];
			
			if ( isset( self::$prefsDescriptionSpecifications[$type]['simplifier'] ) ) {
				$simplify = self::$prefsDescriptionSpecifications[$type]['simplifier'];
				$prefs = call_user_func( $simplify, $prefDescription, $prefs );
			} else {
				$prefDefault = $prefDescription['default'];
				if ( $prefs[$prefName] === $prefDefault ) {
					unset( $prefs[$prefName] );
				}
			}
		}
	}
	
	//Simplifier for 'composite' type fields
	private static function simplifyCompositePref( $prefDescription, $prefs ) {
		$name = $prefDescription['name'];
		if ( array_key_exists( $name, $prefs ) ) {
			self::simplifyPrefs( $prefDescription, $prefs[$name] );
			if ( count( $prefs[$name] ) == 0 ) {
				unset( $prefs[$name] );
			}
		}
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
	 * Returns the list of messages used by a field. If the field type specifications define a "getMessages" method,
	 * uses it, otherwise returns the message in the 'label' member (if any).
	 */
	private static function getFieldMessages( $fieldDescription ) {
		$type = $fieldDescription['type'];
		$prefSpec = self::$prefsDescriptionSpecifications[$type];
		if ( isset( $prefSpec['getMessages'] ) ) {
			$getMessages = $prefSpec['getMessages'];
			return call_user_func( $getMessages, $fieldDescription );
		} else {
			if ( isset( $fieldDescription['label'] ) && self::isMessage( $fieldDescription['label'] ) ) {
				return array( substr( $fieldDescription['label'], 1 ) );
			}
		}
		return array();
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
		foreach ( $prefsDescription['fields'] as $fieldDescription ) {
			$msgs = array_merge( $msgs, self::getFieldMessages( $fieldDescription ) );
		}
		return array_unique( $msgs );
	}
	
	//Returns the messages for a 'select' field description
	private static function getSelectMessages( $prefDescription ) {
		$msgs = array();
		foreach ( $prefDescription['options'] as $option ) {
			$optName = $option['name'];
			if ( self::isMessage( $optName ) ) {
				$msgs[] = substr( $optName, 1 );
			}
		}
		return array_unique( $msgs );
	}

	//Returns the messages for a 'bundle' field description
	private static function getBundleMessages( $prefDescription ) {
		//returns the union of all messages of all sections, plus section names
		$msgs = array();
		foreach ( $prefDescription['sections'] as $sectionDescription ) {
			$msgs = array_merge( $msgs, self::getMessages( $sectionDescription ) );
			$sectionTitle = $sectionDescription['title']; 
			if ( self::isMessage( $sectionTitle ) ) {
				$msgs[] = substr( $sectionTitle, 1 );
			}
		}
		return array_unique( $msgs );
	}
	
	//Returns the messages for a 'list' field description
	private static function getListMessages( $prefDescription ) {
		return self::getFieldMessages( $prefDescription['field'] );
	}
	
	//Returns the default value of a 'composite' field, that is the object of the
	//default values of its subfields.
	private static function getCompositeDefault( $prefDescription ) {
		return self::getDefaults( $prefDescription );
	}
	
	//Returns the messages for a 'composite' field description
	private static function getCompositeMessages( $prefDescription ) {
		return self::getMessages( $prefDescription );
	}	
}
