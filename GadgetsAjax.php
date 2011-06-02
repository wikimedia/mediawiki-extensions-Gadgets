<?php

class GadgetsAjax {
	
	//Common validation code
	//Checks if the user is logged and check params syntax
	//returns error string if vaildation is failed, true otherwise
	private static function validateSyntax( $args ) {
		$user = RequestContext::getMain()->getUser();
		if ( $user->isAnon() ) {
			return '<err#>' . wfMsgExt( 'gadgets-ajax-notallowed', 'parseinline' );
		}

		//checks if all params are of the form 'param|value'
		foreach ( $args as $arg ) {
			$set = explode( '|', $arg, 2 );
			if ( count( $set ) != 2 ) {
				return '<err#>' . wfMsgExt( 'gadgets-ajax-wrongparams', 'parseinline' );
			}
		}
		
		return true;
	}
	
	public static function getPreferences( /* args */ ) {
		//params are in the format "param|val"
		$args = func_get_args();

		$res = self::validateSyntax( $args );
		if ( $res !== true ) {
			return $res;
		}

		foreach ( $args as $arg ) {
			list( $par, $val ) = explode( '|', $arg, 2 );;
			
			switch( $par ) {
				case "gadget":
					$gadget = $val;
					break;
				default:
					return '<err#>' . wfMsgExt( 'gadgets-ajax-wrongparams', 'parseinline' );
			}
		}

		if ( !isset( $gadget ) ) {
			return '<err#>' . wfMsgExt( 'gadgets-ajax-wrongparams', 'parseinline' );
		}
		
		
		$prefsDescriptionJson = Gadget::getGadgetPrefsDescription( $gadget );
		$prefsDescription = FormatJson::decode( $prefsDescriptionJson, true );
		
		if ( $prefsDescription === null ) {
			//either the gadget doesn't exists or it exists but it has no prefs
			return '<err#>' . wfMsgExt( 'gadgets-ajax-wrongparams', 'parseinline' );
		}

		$user = RequestContext::getMain()->getUser();
		$userPrefs = Gadget::getUserPrefs( $user, $gadget );

		//Add user preferences to preference description
		foreach ( $userPrefs as $pref => $value ) {
			$prefsDescription['fields'][$pref]['value'] = $value;
		}		

		return FormatJson::encode( $prefsDescription );
	}
	
	public static function setPreferences( /* args */ ) {
		//TODO: should probably add tokens
		
		//params are in the format "param|val"
		$args = func_get_args();

		$res = self::validateSyntax( $args );
		if ( $res !== true ) {
			return $res;
		}

		foreach ( $args as $arg ) {
			list( $par, $val ) = explode( '|', $arg, 2 );;
			
			switch( $par ) {
				case "gadget":
					$gadget = $val;
					break;
				case "json":
					$json = $val;
					break;
				default:
					return '<err#>' . wfMsgExt( 'gadgets-ajax-wrongparams', 'parseinline' );
			}
		}

		if ( !isset( $gadget ) || !isset( $json ) ) {
			return '<err#>' . wfMsgExt( 'gadgets-ajax-wrongparams', 'parseinline' );
		}
		
		$prefsDescriptionJson = Gadget::getGadgetPrefsDescription( $gadget );
		$prefsDescription = FormatJson::decode( $prefsDescriptionJson, true );
		
		if ( $prefsDescription === null ) {
			//either the gadget doesn't exists or it exists but it has no prefs
			return '<err#>' . wfMsgExt( 'gadgets-ajax-wrongparams', 'parseinline' );
		}
		
		$userPrefs = FormatJson::decode( $json, true );
		
		if ( $userPrefs === null ) {
			return '<err#>' . wfMsgExt( 'gadgets-ajax-wrongparams', 'parseinline' );
		}
		
		$user = RequestContext::getMain()->getUser();
		
		if ( Gadget::setUserPrefs( $user, $gadget, $userPrefs ) ) {
			return 'true';			
		} else {
			return 'false';
		}
	}
}
