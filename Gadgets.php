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

define( 'NS_GADGET', 2300 );
define( 'NS_GADGET_TALK', 2301 );

$wgNamespaceProtection[NS_GADGET][] = array( 'gadgets-edit' );
$wgAvailableRights = array_merge( $wgAvailableRights, array(
	'gadgets-edit',
	'gadgets-manager-create',
	'gadgets-manager-delete',
	'gadgets-manager-view',
	'gadgets-manager-modify'
) );

$wgHooks['ArticleSaveComplete'][]           = 'GadgetHooks::articleSaveComplete';
$wgHooks['BeforePageDisplay'][]             = 'GadgetHooks::beforePageDisplay';
$wgHooks['CanonicalNamespaces'][]           = 'GadgetHooks::canonicalNamespaces';
$wgHooks['GetPreferences'][]                = 'GadgetHooks::getPreferences';
$wgHooks['LoadExtensionSchemaUpdates'][]    = 'GadgetHooks::loadExtensionSchemaUpdates';
$wgHooks['ResourceLoaderRegisterModules'][] = 'GadgetHooks::registerModules';
$wgHooks['UnitTestsList'][]                 = 'GadgetHooks::unitTestsList';

$dir = dirname(__FILE__) . '/';
$wgExtensionMessagesFiles['Gadgets'] = $dir . 'Gadgets.i18n.php';
$wgExtensionMessagesFiles['GadgetsNamespaces'] = $dir . 'Gadgets.namespaces.php';
$wgExtensionAliasesFiles['Gadgets'] = $dir . 'Gadgets.alias.php';

$wgAutoloadClasses['ApiQueryGadgetCategories'] = $dir . 'ApiQueryGadgetCategories.php';
$wgAutoloadClasses['ApiQueryGadgets'] = $dir . 'ApiQueryGadgets.php';
$wgAutoloadClasses['Gadget'] = $dir . 'Gadgets_body.php';
$wgAutoloadClasses['GadgetHooks'] = $dir . 'Gadgets_body.php';
$wgAutoloadClasses['GadgetResourceLoaderModule'] = $dir . 'Gadgets_body.php';
$wgAutoloadClasses['SpecialGadgets'] = $dir . 'SpecialGadgets.php';

$wgSpecialPages['Gadgets'] = 'SpecialGadgets';
$wgSpecialPageGroups['Gadgets'] = 'wiki';

$wgAPIListModules['gadgetcategories'] = 'ApiQueryGadgetCategories';
$wgAPIListModules['gadgets'] = 'ApiQueryGadgets';
