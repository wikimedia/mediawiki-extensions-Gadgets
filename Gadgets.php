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
$wgAutoloadClasses['GadgetOptionsResourceLoaderModule'] = $dir . 'backend/GadgetOptionsResourceLoaderModule.php';
$wgAutoloadClasses['GadgetPrefs'] = $dir . 'backend/GadgetPrefs.php';
$wgAutoloadClasses['GadgetsMainModule'] = $dir . 'backend/GadgetsMainModule.php';
$wgAutoloadClasses['SpecialGadgets'] = $dir . 'ui/SpecialGadgets.php';

$wgSpecialPages['Gadgets'] = 'SpecialGadgets';
$wgSpecialPageGroups['Gadgets'] = 'wiki';

$wgAPIModules['setgadgetprefs'] = 'ApiSetGadgetPrefs';
$wgAPIModules['getgadgetprefs'] = 'ApiGetGadgetPrefs';

$wgAjaxExportList[] = 'GadgetsAjax::getPreferences';
$wgAjaxExportList[] = 'GadgetsAjax::setPreferences';

$wgResourceModules['ext.gadgets'] = array(
	'class' 		=> 'GadgetsMainModule',
);

$wgResourceModules['jquery.validate'] = array(
	'scripts' 		=> array( 'jquery.validate.js' ),
	'dependencies' 	=> array( 'jquery' ),
	'localBasePath' => $dir . 'ui/resources/',
	'remoteExtPath' => 'Gadgets/ui/resources'
);

$wgResourceModules['jquery.farbtastic'] = array(
	'scripts' 		=> array( 'jquery.farbtastic.js' ),
	'styles'        => array( 'jquery.farbtastic.css' ),
	'dependencies' 	=> array( 'jquery', 'jquery.colorUtil' ),
	'localBasePath' => $dir . 'ui/resources/',
	'remoteExtPath' => 'Gadgets/ui/resources'
);

$wgResourceModules['jquery.formBuilder'] = array(
	'scripts' 		=> array( 'jquery.formBuilder.js' ),
	'styles'        => array( 'jquery.formBuilder.css' ),
	'dependencies' 	=> array(
		'jquery', 'jquery.ui.slider', 'jquery.ui.datepicker', 'jquery.ui.position',
		'jquery.ui.draggable', 'jquery.ui.droppable', 'jquery.ui.sortable', 'jquery.ui.dialog',
		'jquery.ui.tabs', 'jquery.farbtastic', 'jquery.colorUtil', 'jquery.validate'
	),
	'messages'      => array(
		'gadgets-formbuilder-required', 'gadgets-formbuilder-minlength', 'gadgets-formbuilder-maxlength',
		'gadgets-formbuilder-min', 'gadgets-formbuilder-max', 'gadgets-formbuilder-integer', 'gadgets-formbuilder-date',
		'gadgets-formbuilder-color', 'gadgets-formbuilder-list-required', 'gadgets-formbuilder-list-minlength',
		'gadgets-formbuilder-list-maxlength', 'gadgets-formbuilder-scalar',
		'gadgets-formbuilder-editor-ok', 'gadgets-formbuilder-editor-cancel', 'gadgets-formbuilder-editor-move-field',
		'gadgets-formbuilder-editor-delete-field', 'gadgets-formbuilder-editor-edit-field', 'gadgets-formbuilder-editor-edit-field-title', 'gadgets-formbuilder-editor-insert-field',
		'gadgets-formbuilder-editor-chose-field', 'gadgets-formbuilder-editor-chose-field-title', 'gadgets-formbuilder-editor-create-field-title',
		'gadgets-formbuilder-editor-duplicate-name', 'gadgets-formbuilder-editor-delete-section', 'gadgets-formbuilder-editor-new-section',
		'gadgets-formbuilder-editor-edit-section', 'gadgets-formbuilder-editor-chose-title', 'gadgets-formbuilder-editor-chose-title-title'
	),
	'localBasePath' => $dir . 'ui/resources/',
	'remoteExtPath' => 'Gadgets/ui/resources'
);
