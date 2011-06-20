<?php
/**
 * Gadgets extension - lets users select custom javascript gadgets
 *
 *
 * For more info see http://mediawiki.org/wiki/Extension:Gadgets
 *
 * @file
 * @ingroup Extensions
 * @author Daniel Kinzler, brightbyte.de
 * @copyright © 2007 Daniel Kinzler
 * @license GNU General Public Licence 2.0 or later
 */

if( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

if ( version_compare( $wgVersion, '1.19alpha', '<' ) ) {
	die( "This version of Extension:Gadgets requires MediaWiki 1.19+\n" );
}

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Gadgets',
	'author' => array( 'Daniel Kinzler', 'Max Semenik' ),
	'url' => 'http://mediawiki.org/wiki/Extension:Gadgets',
	'descriptionmsg' => 'gadgets-desc',
);

$wgHooks['ArticleSaveComplete'][]           = 'GadgetHooks::articleSaveComplete';
$wgHooks['BeforePageDisplay'][]             = 'GadgetHooks::beforePageDisplay';
$wgHooks['GetPreferences'][]                = 'GadgetHooks::getPreferences';
$wgHooks['ResourceLoaderRegisterModules'][] = 'GadgetHooks::registerModules';
$wgHooks['UnitTestsList'][]                 = 'GadgetHooks::unitTestsList';
$wgHooks['UserLoadOptions'][]               = 'GadgetHooks::userLoadOptions';
$wgHooks['UserSaveOptions'][]               = 'GadgetHooks::userSaveOptions';

$dir = dirname(__FILE__) . '/';
$wgExtensionMessagesFiles['Gadgets'] = $dir . 'Gadgets.i18n.php';
$wgExtensionAliasesFiles['Gadgets'] = $dir . 'Gadgets.alias.php';

$wgAutoloadClasses['ApiQueryGadgetCategories'] = $dir . 'api/ApiQueryGadgetCategories.php';
$wgAutoloadClasses['ApiQueryGadgets'] = $dir . 'api/ApiQueryGadgets.php';
$wgAutoloadClasses['ApiGetGadgetPrefs'] = $dir . 'api/ApiGetGadgetPrefs.php';
$wgAutoloadClasses['ApiSetGadgetPrefs'] = $dir . 'api/ApiSetGadgetPrefs.php';
$wgAutoloadClasses['Gadget'] = $dir . 'backend/Gadget.php';
$wgAutoloadClasses['GadgetHooks'] = $dir . 'backend/GadgetHooks.php';
$wgAutoloadClasses['GadgetResourceLoaderModule'] = $dir . 'backend/GadgetResourceLoaderModule.php';
$wgAutoloadClasses['GadgetsMainModule'] = $dir . 'ui/GadgetsMainModule.php';
$wgAutoloadClasses['SpecialGadgets'] = $dir . 'ui/SpecialGadgets.php';

$wgSpecialPages['Gadgets'] = 'SpecialGadgets';
$wgSpecialPageGroups['Gadgets'] = 'wiki';

$wgAPIModules['setgadgetprefs'] = 'ApiSetGadgetPrefs';
$wgAPIModules['getgadgetprefs'] = 'ApiGetGadgetPrefs';

$wgAjaxExportList[] = 'GadgetsAjax::getPreferences';
$wgAjaxExportList[] = 'GadgetsAjax::setPreferences';

$wgResourceModules['ext.gadgets'] = array(
	'class' 		=> 'GadgetsMainModule'
);

$wgResourceModules['jquery.validate'] = array(
	'scripts' 		=> array( 'jquery.validate.js' ),
	'dependencies' 	=> array( 'jquery' ),
	'localBasePath' => $dir . 'ui/resources/',
	'remoteExtPath' => 'Gadgets/ui/resources'
);

$wgResourceModules['jquery.formBuilder'] = array(
	'scripts' 		=> array( 'jquery.formBuilder.js' ),
	'dependencies' 	=> array( 'jquery', 'jquery.validate' ),
	'messages'      => array(
		'gadgets-formbuilder-required', 'gadgets-formbuilder-minlength', 'gadgets-formbuilder-maxlength',
		'gadgets-formbuilder-min', 'gadgets-formbuilder-max', 'gadgets-formbuilder-integer'
	),
	'localBasePath' => $dir . 'ui/resources/',
	'remoteExtPath' => 'Gadgets/ui/resources'
);

$wgResourceModules['ext.gadgets.preferences'] = array(
	'scripts' 		=> array( 'ext.gadgets.preferences.js' ),
	'styles'        => array( 'ext.gadgets.preferences.css' ),
	'dependencies' 	=> array(
		'jquery', 'jquery.json', 'jquery.ui.dialog', 'jquery.formBuilder',
		'mediawiki.htmlform', 'ext.gadgets'
	),
	'messages'      => array(
		'gadgets-configure', 'gadgets-configuration-of', 'gadgets-prefs-save', 'gadgets-prefs-cancel',
		'gadgets-unexpected-error', 'gadgets-save-success', 'gadgets-save-failed'
	),
	'localBasePath' => $dir . 'ui/resources/',
	'remoteExtPath' => 'Gadgets/ui/resources'
);
