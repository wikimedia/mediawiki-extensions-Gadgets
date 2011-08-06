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

/*** Configuration ***/

/**
 * Add gadget repositories here.
 * 
 * For foreign DB-based gadget repositories, use:
 * // TODO: Document better by looking at WMF ForeignFileRepo config
 * $wgGadgetRepositories[] = array(
 * 	'class' => 'ForeignDBGadgetRepo',
 * 	'source' => 'mediawikiwiki', // Name of the ResourceLoader source for the foreign wiki, see $wgResourceLoaderSources
 * 	'dbType' => 'mysql', // Database type of the foreign wiki's database
 * 	'dbServer' => 'db123', // Database host for the foreign wiki's master database
 * 	'dbUser' => 'username', // User name for the foreign wiki's database
 * 	'dbPassword' => 'password', // Password for the foreign wiki's database
 * 	'dbName' => 'mediawikiwiki', // Name of the foreign wiki's database
 *	// TODO: Make this the default?
 * 	'dbFlags' => ( $wgDebugDumpSql ? DBO_DEBUG : 0 ) | DBO_DEFAULT // Use this value unless you know what you're doing
 * 	'tablePrefix' => 'mw_', // Table prefix for the foreign wiki's database, or '' if no prefix
 //*	'hasSharedCache' => true, // Whether the foreign wiki's cache is accessible through $wgMemc   // TODO: needed?
 * );
 * 
 * For foreign API-based gadget repositories, use:
 * $wgGadgetRepositories[] = array(
 * 	'class' => 'ForeignAPIGadgetRepo',
 *	// TODO
 * );
 */
$wgGadgetRepositories = array(
	array(
		// Default local gadget repository. Doesn't need any parameters
		'class' => 'LocalGadgetRepo',
	)
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

$wgAutoloadClasses['ApiQueryGadgetCategories'] = $dir . 'api/ApiQueryGadgetCategories.php';
$wgAutoloadClasses['ApiQueryGadgets'] = $dir . 'api/ApiQueryGadgets.php';
$wgAutoloadClasses['ForeignDBGadgetRepo'] = $dir . 'backend/ForeignDBGadgetRepo.php';
$wgAutoloadClasses['Gadget'] = $dir . 'backend/Gadget.php';
$wgAutoloadClasses['GadgetHooks'] = $dir . 'GadgetHooks.php';
$wgAutoloadClasses['GadgetRepo'] = $dir . 'backend/GadgetRepo.php';
$wgAutoloadClasses['GadgetResourceLoaderModule'] = $dir . 'backend/GadgetResourceLoaderModule.php';
$wgAutoloadClasses['LocalGadgetRepo'] = $dir . 'backend/LocalGadgetRepo.php';
$wgAutoloadClasses['SpecialGadgets'] = $dir . 'SpecialGadgets.php';

$wgSpecialPages['Gadgets'] = 'SpecialGadgets';
$wgSpecialPageGroups['Gadgets'] = 'wiki';

$wgAPIListModules['gadgetcategories'] = 'ApiQueryGadgetCategories';
$wgAPIListModules['gadgets'] = 'ApiQueryGadgets';

$wgLogTypes[] = 'gadgetman';
$wgLogNames['gadgetman'] = 'gadgets-gadgetmanlog-page'; // TODO define
$wgLogHeaders['gadgetman'] = 'gadgets-gadgetmanlog-text'; // TODO define
$wgLogActions['gadgetman/create'] = 'gadgets-gadgetmanlog-createentry';
$wgLogActions['gadgetman/modify'] = 'gadgets-gadgetmanlog-modifyentry';
$wgLogActions['gadgetman/delete'] = 'gadgets-gadgetmanlog-deleteentry';
// TODO add as needed
// TODO: create and modify will not have a summary, figure out how well that fares. User creation also doesn't have one
#$wgLogActionsHandlers['gadgetman/create'] = '...';
#$wgLogActionsHandlers['gadgetman/modify'] = '...';
#$wgLogActionsHandlers['gadgetman/delete'] = '...';
