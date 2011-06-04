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

if ( version_compare( $wgVersion, '1.17alpha', '<' ) ) {
	die( "This version of Extension:Gadgets requires MediaWiki 1.17+\n" );
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

$dir = dirname(__FILE__) . '/';
$wgExtensionMessagesFiles['Gadgets'] = $dir . 'Gadgets.i18n.php';
$wgExtensionAliasesFiles['Gadgets'] = $dir . 'Gadgets.alias.php';

$wgAutoloadClasses['ApiQueryGadgetCategories'] = $dir . 'ApiQueryGadgetCategories.php';
$wgAutoloadClasses['ApiQueryGadgets'] = $dir . 'ApiQueryGadgets.php';
$wgAutoloadClasses['Gadget'] = $dir . 'Gadgets_body.php';
$wgAutoloadClasses['GadgetHooks'] = $dir . 'Gadgets_body.php';
$wgAutoloadClasses['GadgetResourceLoaderModule'] = $dir . 'Gadgets_body.php';
$wgAutoloadClasses['SpecialGadgets'] = $dir . 'SpecialGadgets.php';
$wgAutoloadClasses['GadgetsGlobalModule'] = $dir . 'Gadgets_body.php';
$wgAutoloadClasses['GadgetsAjax'] = $dir . 'GadgetsAjax.php';

$wgSpecialPages['Gadgets'] = 'SpecialGadgets';
$wgSpecialPageGroups['Gadgets'] = 'wiki';

$wgAPIListModules['gadgetcategories'] = 'ApiQueryGadgetCategories';
$wgAPIListModules['gadgets'] = 'ApiQueryGadgets';

$wgAjaxExportList[] = 'GadgetsAjax::getPreferences';
$wgAjaxExportList[] = 'GadgetsAjax::setPreferences';

$wgResourceModules['ext.gadgets'] = array(
	'class' 		=> 'GadgetsGlobalModule'
);

$wgResourceModules['jquery.validate'] = array(
	'scripts' 		=> array( 'jquery.validate.js' ),
	'dependencies' 	=> array( 'jquery' ),
	'localBasePath' => $dir . 'modules/',
	'remoteExtPath' => 'Gadgets/modules'
);

$wgResourceModules['jquery.formBuilder'] = array(
	'scripts' 		=> array( 'jquery.formBuilder.js' ),
	'dependencies' 	=> array( 'jquery', 'jquery.validate' ),
	'messages'      => array( 'gadgets-formbuilder-required', 'gadgets-formbuilder-minlength', 'gadgets-formbuilder-maxlength' ),
	'localBasePath' => $dir . 'modules/',
	'remoteExtPath' => 'Gadgets/modules'
);

$wgResourceModules['ext.gadgets.preferences'] = array(
	'scripts' 		=> array( 'ext.gadgets.preferences.js' ),
	'dependencies' 	=> array(
		'jquery', 'jquery.json', 'jquery.ui.dialog', 'jquery.formBuilder',
		'mediawiki.htmlform', 'ext.gadgets'
	),
	'messages'      => array(
		'gadgets-configure', 'gadgets-configuration-of', 'gadgets-unexpected-error',
		'gadgets-save-success', 'gadgets-save-failed'
	),
	'localBasePath' => $dir . 'modules/',
	'remoteExtPath' => 'Gadgets/modules'
);
