<?php
/**
 * Gadgets extension - lets users select custom javascript gadgets
 *
 * 
 * For more info see http://mediawiki.org/wiki/Extension:Gadgets
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Daniel Kinzler, brightbyte.de
 * @copyright © 2007 Daniel Kinzler
 * @licence GNU General Public Licence 2.0 or later
 */

if( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

$wgExtensionCredits['other'][] = array( 
	'name' => 'Gadgets', 
	'author' => 'Daniel Kinzler', 
	'url' => 'http://mediawiki.org/wiki/Extension:Gadgets',
	'description' => 'lets users select custom javascript gadgets',
);

$wgHooks['InitPreferencesForm'][] = 'wfGadgetsInitPreferencesForm';
$wgHooks['RenderPreferencesForm'][] = 'wfGadgetsRenderPreferencesForm';
$wgHooks['ResetPreferences'][] = 'wfGadgetsResetPreferences';
$wgHooks['BeforePageDisplay'][] = 'wfGadgetsBeforePageDisplay';
$wgHooks['ArticleSave'][] = 'wfGadgetsArticleSave';
$wgHooks['LoadAllMessages'][] = "loadGadgetsI18n";

$wgAutoloadClasses['SpecialGadgets'] = dirname( __FILE__ ) . '/SpecialGadgets.php';
$wgSpecialPages['Gadgets'] = 'SpecialGadgets';

function wfGadgetsArticleSave( &$article ) {
	//purge from cache if need be
	$title = $article->mTitle;
	if( $title->getNamespace() == NS_MEDIAWIKI && $title->getText() == 'Gadgets-definition' ) {
		global $wgMemc, $wgDBname;
		$key = "$wgDBname:gadgets-definition";
		$wgMemc->delete( $key );
		wfDebug("wfGadgetsArticleSave: MediaWiki:Gadgets-definition edited, cache entry $key purged\n");
	}
	return true;
}

function wfLoadGadgets() {
	static $gadgets = NULL;

	if ( $gadgets !== NULL ) return $gadgets;

	$struct = wfLoadGadgetsStructured();
	if ( !$struct ) {
		$gadgets = $struct;
		return $gadgets;
	}

	$gadgets = array();
	foreach ( $struct as $section => $entries ) {
		$gadgets = array_merge( $gadgets, $entries );
	}

	return $gadgets;
}

function wfLoadGadgetsStructured() {
	global $wgContLang;
	global $wgMemc, $wgDBname;

	static $gadgets = NULL;
	if ( $gadgets !== NULL ) return $gadgets;

	$key = "$wgDBname:gadgets-definition";

	//cached?
	$gadgets = $wgMemc->get( $key );
	if ( $gadgets !== NULL ) return $gadgets;

	$g = wfMsgForContentNoTrans( "gadgets-definition" );
	if ( wfEmptyMsg( "gadgets-definition", $g ) ) {
		$gadgets = false;
		return $gadgets;
	}

	$g = preg_replace( '/<!--.*-->/s', '', $g );
	$g = preg_split( '/(\r\n|\r|\n)+/', $g );

	$gadgets = array();
	$section = '';

	foreach ( $g as $line ) {
		if ( preg_match( '/^==+ *([^*:\s|]+?)\s*==+\s*$/', $line, $m ) ) {
			$section = $m[1];
		}
		else if ( preg_match( '/^\*+ *([a-zA-Z](?:[-_:.\w\d ]*[a-zA-Z0-9])?)\s*((\|[^|]*)+)\s*$/', $line, $m ) ) {
			//NOTE: the gadget name is used as part of the name of a form field,
			//      and must follow the rules defined in http://www.w3.org/TR/html4/types.html#type-cdata
			//      Also, title-normalization applies.
			$name = $wgContLang->lcfirst( str_replace(' ', '_', $m[1] ) );

			$code = $wgContLang->lcfirst( preg_split( '/\s*\|\s*/', $m[2], -1, PREG_SPLIT_NO_EMPTY ) );

			if ( $code ) {
				$gadgets[$section][$name] = $code;
			}
		}
	}

	//cache for a while. gets purged automatically when MediaWiki:Gadgets-definition is edited
	$wgMemc->set( $key, $gadgets, 900 );
	wfDebug("wfLoadGadgetsStructured: MediaWiki:Gadgets-definition parsed, cache entry $key updated\n");

	return $gadgets;
}

function wfGadgetsInitPreferencesForm( &$prefs, &$request ) {
	$gadgets = wfLoadGadgets();
	if ( !$gadgets ) return true;

	foreach ( $gadgets as $gname => $code ) {
		$tname = "gadget-$gname";
		$prefs->mToggles[$tname] = $request->getCheck( "wpOp$tname" ) ? 1 : 0;
	}

	return true;
}

function wfGadgetsResetPreferences( &$prefs, &$user ) {
	$gadgets = wfLoadGadgets();
	if ( !$gadgets ) return true;

	foreach ( $gadgets as $gname => $code ) {
		$tname = "gadget-$gname";
		$prefs->mToggles[$tname] = $user->getOption( $tname );
	}

	return true;
}

function wfGadgetsRenderPreferencesForm( &$prefs, &$out ) {
	$gadgets = wfLoadGadgetsStructured();
	if ( !$gadgets ) return true;

	loadGadgetsI18n();

	$out->addHtml( "\n<fieldset>\n<legend>" . wfMsgHtml( 'gadgets-prefs' ) . "</legend>\n" );

	$out->addHtml( "<p>" . wfMsgWikiHtml( 'gadgets-prefstext' ) . "</p>\n" );

	$msgOpt = array( 'parseinline', 'parsemag' );

	foreach ( $gadgets as $section => $entries ) {
		if ( $section !== false && $section !== '' ) {
			$ttext = wfMsgExt( "gadget-section-$section", $msgOpt );
			$out->addHtml( "\n<h2>" . $ttext . "</h2>\n" );
		}

		foreach ( $entries as $gname => $code ) {
			$tname = "gadget-$gname";
			$ttext = wfMsgExt( $tname, $msgOpt );
			$checked = @$prefs->mToggles[$tname] == 1 ? ' checked="checked"' : '';
			$disabled = '';
	
			$out->addHtml( "<div class='toggle'><input type='checkbox' value='1' id=\"$tname\" name=\"wpOp$tname\"$checked$disabled />" .
				" <span class='toggletext'><label for=\"$tname\">$ttext</label></span></div>\n" );
		}
	}

	$out->addHtml( "</fieldset>\n\n" );

	return true;
}

function wfGadgetsBeforePageDisplay( &$out ) {
	global $wgUser, $wgTitle;
	if ( !$wgUser->isLoggedIn() ) return true;

	//disable all gadgets on Special:Preferences
	if ( $wgTitle->getNamespace() == NS_SPECIAL ) {
		$name = SpecialPage::resolveAlias( $wgTitle->getText() );
		if ( $name == "Preferences" ) return true;
	}

	$gadgets = wfLoadGadgets();
	if ( !$gadgets ) return true;

	$done = array();

	foreach ( $gadgets as $gname => $code ) {
		$tname = "gadget-$gname";
		if ( $wgUser->getOption( $tname ) ) {
			wfApplyGadgetCode( $code, $out, $done );
		}
	}

	return true;
}

function wfApplyGadgetCode( $code, &$out, &$done ) {
	global $wgSkin, $wgJsMimeType;

	//FIXME: stuff added via $out->addScript appears below usercss and userjs in the head tag.
	//       but we'd want it to appear above explicit user stuff, so it can be overwritten.
	foreach ( $code as $codePage ) {
		//include only once
		if ( isset( $done[$codePage] ) ) continue;
		$done[$codePage] = true;

		$t = Title::makeTitleSafe( NS_MEDIAWIKI, "Gadget-$codePage" );
		if ( !$t ) continue;

		if ( preg_match( '/\.js/', $codePage ) ) {
			$u = $t->getLocalURL( 'action=raw&ctype=' . $wgJsMimeType );
			$out->addScript( '<script type="' . $wgJsMimeType . '" src="' . htmlspecialchars( $u ) . '"></script>' . "\n" );
		}
		else if ( preg_match( '/\.css/', $codePage ) ) {
			$u = $t->getLocalURL( 'action=raw&ctype=text/css' );
			$out->addScript( '<style type="text/css">/*<![CDATA[*/ @import "' . $u . '"; /*]]>*/</style>' . "\n" );
		}
	}
}

function loadGadgetsI18n() {
	global $wgLang, $wgMessageCache;

	static $initialized = false;

	if ( $initialized )
		return true;

	$messages = array();

	$f = dirname( __FILE__ ) . '/Gadgets.i18n.php';
	include( $f );

	$f = dirname( __FILE__ ) . '/Gadgets.i18n.' . $wgLang->getCode() . '.php';
	if ( file_exists( $f ) ) {
		$messages_base = $messages;
		$messages = array();

		include( $f );

		$messages = array_merge( $messages_base, $messages );
	}

	$initialized = true;
	$wgMessageCache->addMessages( $messages );

	return true;
}
