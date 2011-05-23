<?php

class GadgetsAjax {	
	public static function getUI( /*$args...*/ ) {
		global $wgUser;
		
		if ( $wgUser->isAnon() ) {
			return '<err#>' . wfMsgExt( 'gadgets-ajax-notallowed', 'parseinline' );
		}
		
		//params are in the format "param|val"
		$args = func_get_args();

		foreach ( $args as $arg ) {
			$set = explode( '|', $arg, 2 );
			if ( count( $set ) != 2 ) {
				return '<err#>' . wfMsgExt( 'gadgets-ajax-wrongparams', 'parseinline' );
			}
			
			list( $par, $val ) = $set;
			
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
		
		$prefs_json = Gadget::getGadgetPrefsDescription( $gadget );
		
		//If $gadget doesn't exists or it doesn't have preferences, something is wrong
		if ( $prefs_json === null || $prefs_json === '' ) {
			return '<err#>' . wfMsgExt( 'gadgets-ajax-wrongparams', 'parseinline' );
		}
		
		$prefs = json_decode( $prefs_json, true );
		
		//If it's not valid JSON, signal an error
		if ( $prefs === null ) {
			return '<err#>' . wfMsgExt( 'gadgets-ajax-wrongsyntax', 'parseinline' );
		}
		
		//TODO: options of "select" and similar fields cannot be passed as messages
		$form = new HTMLForm( $prefs, RequestContext::getMain() );
		
		$form->mFieldData = Gadget::getUserPrefs( $wgUser, $gadget );

		//TODO: HTMLForm::getBody is not meant to be public, a refactoring is needed		
		return $form->getBody();
	}
	
	public static function setPreferences( /* args */ ) {
		//TODO
		throw new MWException( 'Not implemented' );
	}
}
