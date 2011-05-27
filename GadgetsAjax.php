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
	
	public static function getUI( /*$args...*/ ) {
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
		
		$prefsJson = Gadget::getGadgetPrefsDescription( $gadget );
		
		//If $gadget doesn't exists or it doesn't have preferences, something is wrong
		if ( $prefsJson === null || $prefsJson === '' ) {
			return '<err#>' . wfMsgExt( 'gadgets-ajax-wrongparams', 'parseinline' );
		}
		
		$prefs = FormatJson::decode( $prefsJson, true );
		
		//If it's not valid JSON, signal an error
		if ( $prefs === null ) {
			return '<err#>' . wfMsgExt( 'gadgets-ajax-wrongsyntax', 'parseinline' );
		}
		
		//TODO: options of "select" and similar fields cannot be passed as messages
		$form = new HTMLForm( $prefs, RequestContext::getMain() );
		
		$user = RequestContext::getMain()->getUser();
		
		$form->mFieldData = Gadget::getUserPrefs( $user, $gadget );

		//TODO: HTMLForm::getBody is not meant to be public, a refactoring is needed
		//      (or a completely different solution)
		return $form->getBody();
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
		
		foreach ( $userPrefs as $pref => $value ) {
			if ( !isset( $prefsDescription[$pref] ) ){
				//Nonexisting configuration parameter; ignore it
				unset( $userPrefs[$pref] );
			} else {
				//TODO: convert values to proper type, check coherency with specification
				//      and fix fields that don't pass validation
			}
		}

		$user = RequestContext::getMain()->getUser();
		Gadget::setUserPrefs( $user, $gadget, $userPrefs );
		
		return 'true';
	}
}
