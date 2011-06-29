<?php

/**
 * Static methods for gadget preferences parsing, validation and so on.
 * 
 * @author Salvatore Ingala
 * @license GNU General Public Licence 2.0 or later
 * 
 */

class GadgetPrefs {
	
	//Syntax specifications of preference description language
	private static $prefsDescriptionSpecifications = array(
		'boolean' => array(
			'default' => array(
				'isMandatory' => true,
				'checker' => 'is_bool'
			),
			'label' => array(
				'isMandatory' => true,
				'checker' => 'is_string'
			)
		),
		'string' => array(
			'default' => array(
				'isMandatory' => true,
				'checker' => 'is_string'
			),
			'label' => array(
				'isMandatory' => true,
				'checker' => 'is_string'
			),
			'required' => array(
				'isMandatory' => false,
				'checker' => 'is_bool'
			),
			'minlength' => array(
				'isMandatory' => false,
				'checker' => 'is_integer'
			),
			'maxlength' => array(
				'isMandatory' => false,
				'checker' => 'is_integer'
			)
		),
		'number' => array(
			'default' => array(
				'isMandatory' => true,
				'checker' => 'GadgetPrefs::isFloatOrIntOrNull'
			),
			'label' => array(
				'isMandatory' => true,
				'checker' => 'is_string'
			),
			'required' => array(
				'isMandatory' => false,
				'checker' => 'is_bool'
			),
			'integer' => array(
				'isMandatory' => false,
				'checker' => 'is_bool'
			),
			'min' => array(
				'isMandatory' => false,
				'checker' => 'GadgetPrefs::isFloatOrInt'
			),
			'max' => array(
				'isMandatory' => false,
				'checker' => 'GadgetPrefs::isFloatOrInt'
			)
		),
		'select' => array(
			'default' => array(
				'isMandatory' => true
			),
			'label' => array(
				'isMandatory' => true,
				'checker' => 'is_string'
			),
			'options' => array(
				'isMandatory' => true,
				'checker' => 'is_array'
			)
		),
		'range' => array(
			'default' => array(
				'isMandatory' => true,
				'checker' => 'GadgetPrefs::isFloatOrIntOrNull'
			),
			'label' => array(
				'isMandatory' => true,
				'checker' => 'is_string'
			),
			'min' => array(
				'isMandatory' => true,
				'checker' => 'GadgetPrefs::isFloatOrInt'
			),
			'max' => array(
				'isMandatory' => true,
				'checker' => 'GadgetPrefs::isFloatOrInt'
			),
			'step' => array(
				'isMandatory' => false,
				'checker' => 'GadgetPrefs::isFloatOrInt'
			)
		)
	);

	//Type-specific checkers for finer validation
	private static $typeCheckers = array(
		'string' => 'GadgetPrefs::checkStringOptionDefinition',
		'number' => 'GadgetPrefs::checkNumberOptionDefinition',
		'select' => 'GadgetPrefs::checkSelectOptionDefinition',
		'range'  => 'GadgetPrefs::checkRangeOptionDefinition',
	);
	
	//Further checks for 'string' options
	private static function checkStringOptionDefinition( $option ) {
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
	
	//Further checks for 'number' options
	private static function checkNumberOptionDefinition( $option ) {
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

	private static function checkSelectOptionDefinition( $option ) {
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
	
	private static function checkRangeOptionDefinition( $option ) {
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
	
	//Checks if the given description of the preferences is valid
	public static function isPrefsDescriptionValid( $prefsDescription ) {
		if ( !is_array( $prefsDescription )
			|| !isset( $prefsDescription['fields'] )
			|| !is_array( $prefsDescription['fields'] )
			|| count( $prefsDescription['fields'] ) == 0 )
		{
			return false;
		}
				
		//Count of mandatory members for each type
		$mandatoryCount = array();
		foreach ( self::$prefsDescriptionSpecifications as $type => $typeSpec ) {
			$mandatoryCount[$type] = 0;
			foreach ( $typeSpec as $fieldName => $fieldSpec ) {
				if ( $fieldSpec['isMandatory'] === true ) {
					++$mandatoryCount[$type];
				}
			}
		}
		
		//TODO: validation of members other than $prefs['fields']
		
		foreach ( $prefsDescription['fields'] as $option => $optionDefinition ) {
			
			//Check if 'type' is set and valid
			if ( !isset( $optionDefinition['type'] ) ) {
				return false;
			}
			
			$type = $optionDefinition['type'];
									
			if ( !isset( self::$prefsDescriptionSpecifications[$type] ) ) {
				return false;
			}
			
			//check $option name compliance
			if ( strlen( $option ) > 40 
				|| !preg_match( '/^[a-zA-Z_][a-zA-Z0-9_]*$/', $option ) )
			{
				return false;
			}
			
			//Check if all fields satisfy specification
			$typeSpec = self::$prefsDescriptionSpecifications[$type];
			$count = 0; //count of present mandatory members
			foreach ( $optionDefinition as $fieldName => $fieldValue ) {
				
				if ( $fieldName == 'type' ) {
					continue; //'type' must not be checked
				}
				
				if ( !isset( $typeSpec[$fieldName] ) ) {
					return false;
				}
				
				if ( $typeSpec[$fieldName]['isMandatory'] ) {
					++$count;
				}
				
				if ( isset( $typeSpec[$fieldName]['checker'] ) ) {
					$checker = $typeSpec[$fieldName]['checker'];
					if ( !call_user_func( $checker, $fieldValue ) ) {
						return false;
					}
				}
			}
			
			if ( $count != $mandatoryCount[$type] ) {
				return false; //not all mandatory members are given
			}
			
			if ( isset( self::$typeCheckers[$type] ) ) {
				//Call type-specific checker for finer validation
				if ( !call_user_func( self::$typeCheckers[$type], $optionDefinition ) ) {
					return false;
				}
			}
			
			//Finally, check that the 'default' fields exists and is valid
			if ( !array_key_exists( 'default', $optionDefinition ) ) {
				return false;
			}
			
			$prefs = array( 'dummy' => $optionDefinition['default'] );
			if ( !self::checkSinglePref( $optionDefinition, $prefs, 'dummy' ) ) {
				return false;
			}
		}
		
		return true;
	}
	
	//Check if a preference is valid, according to description
	//NOTE: we pass both $prefs and $prefName (instead of just $prefs[$prefName])
	//      to allow checking for null.
	private static function checkSinglePref( $prefDescription, $prefs, $prefName ) {

		//isset( $prefs[$prefName] ) would return false for null values
		if ( !array_key_exists( $prefName, $prefs ) ) {
			return false;
		}
	
		$pref = $prefs[$prefName];
	
		switch ( $prefDescription['type'] ) {
			case 'boolean':
				return is_bool( $pref );
			case 'string':
				if ( !is_string( $pref ) ) {
					return false;
				}
				
				$len = strlen( $pref );
				
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
			case 'number':
				if ( !is_float( $pref ) && !is_int( $pref ) && $pref !== null ) {
					return false;
				}

				$required = isset( $prefDescription['required'] ) ? $prefDescription['required'] : true;
				if ( $required === false && $pref === null ) {
					return true;
				}
				
				if ( $pref === null ) {
					return false; //$required === true, so null is not acceptable
				}

				$integer = isset( $prefDescription['integer'] ) ? $prefDescription['integer'] : false;
				
				if ( $integer === true && intval( $pref ) != $pref ) {
					return false; //not integer
				}
				
				if ( isset( $prefDescription['min'] ) ) {
					$min = $prefDescription['min'];
					if ( $pref < $min ) {
						return false; //value below minimum
					}
				}

				if ( isset( $prefDescription['max'] ) ) {
					$max = $prefDescription['max'];
					if ( $pref > $max ) {
						return false; //value above maximum
					}
				}

				return true;
			case 'select':
				$values = array_values( $prefDescription['options'] );
				return in_array( $pref, $values, true );
			case 'range':
				if ( !is_float( $pref ) && !is_int( $pref ) ) {
					return false;
				}
				
				$min = $prefDescription['min'];
				$max = $prefDescription['max'];
				
				if ( $pref < $min || $pref > $max ) {
					return false;
				}
				
				$step = isset( $prefDescription['step'] ) ? $prefDescription['step'] : 1;
				
				if ( $step <= 0 ) {
					return false;
				}
				
				//Valid values are min, min + step, min + 2*step, ...
				//Then ( $pref - $min ) / $step must be close enough to an integer
				$eps = 1.0e-6; //tolerance
				$tmp = ( $pref - $min ) / $step;
				if ( abs( $tmp - floor( $tmp ) ) > $eps ) {
					return false;
				}
				
				return true;
			default:
				return false; //unexisting type
		}
	}

	/**
	 * Checks if $prefs is an array of preferences that passes validation
	 * 
	 * @param $prefsDescription Array: the preferences description to use.
	 * @param $prefs Array: reference of the array of preferences to check.
	 * 
	 * @return boolean true if $prefs passes validation against $prefsDescription, false otherwise.
	 */
	public static function checkPrefsAgainstDescription( $prefsDescription, $prefs ) {
		foreach ( $prefsDescription['fields'] as $prefName => $prefDescription ) {
			if ( !self::checkSinglePref( $prefDescription, $prefs, $prefName ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Fixes $prefs so that it matches the description given by $prefsDescription.
	 * All values of $prefs that fail validation are replaced with default values.
	 * 
	 * @param $prefsDescription Array: the preferences description to use.
	 * @param &$prefs Array: reference of the array of preferences to match.
	 */
	public static function matchPrefsWithDescription( $prefsDescription, &$prefs ) {
		//Remove unexisting preferences from $prefs
		foreach ( $prefs as $prefName => $value ) {
			if ( !isset( $prefsDescription['fields'][$prefName] ) ) {
				unset( $prefs[$prefName] );
			}
		}

		//Fix preferences that fail validation
		foreach ( $prefsDescription['fields'] as $prefName => $prefDescription ) {
			if ( !self::checkSinglePref( $prefDescription, $prefs, $prefName ) ) {
				$prefs[$prefName] = $prefDescription['default'];
			}
		}
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
		$maybeMsgs = array();
		
		if ( isset( $prefsDescription['intro'] ) ) {
			$maybeMsgs[] = $prefsDescription['intro'];
		}
		
		foreach ( $prefsDescription['fields'] as $prefName => $prefDesc ) {
			$maybeMsgs[] = $prefDesc['label'];
			
			if ( $prefDesc['type'] == 'select' ) {
				foreach ( $prefDesc['options'] as $optName => $value ) {
					$maybeMsgs[] = $optName;
				}
			}
		}
		
		$msgs = array();
		foreach ( $maybeMsgs as $msg ) {
			if ( self::isMessage( $msg ) ) {
				$msgs[] = substr( $msg, 1 );
			}
		}
		return array_unique( $msgs );
	}
}
