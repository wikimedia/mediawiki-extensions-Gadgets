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

if ( version_compare( $wgVersion, '1.19c', '<' ) ) { // Needs to be 1.19c because version_compare() works in confusing ways
	die( "This version of Extension:Gadgets requires MediaWiki 1.19+\n" );
}

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Gadgets',
	'author' => array( 'Daniel Kinzler', 'Max Semenik', 'Roan Kattouw', 'Timo Tijhof' ),
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
 * 	// TODO: Make this the default?
 * 	'dbFlags' => ( $wgDebugDumpSql ? DBO_DEBUG : 0 ) | DBO_DEFAULT // Use this value unless you know what you're doing
 * 	'tablePrefix' => 'mw_', // Table prefix for the foreign wiki's database, or '' if no prefix
 * 	'hasSharedCache' => true, // Whether the foreign wiki's cache is accessible through $wgMemc
 * 	'cacheTimeout' => 600, // Expiry for locally cached data, in seconds (optional; default is 600)
 * );
 *
 * For foreign API-based gadget repositories, use:
 * $wgGadgetRepositories[] = array(
 * 	'class' => 'ForeignAPIGadgetRepo',
 * 	'source' => 'mediawikiwiki',
 * 	'cacheTimeout' => 600, // Expiry for locally cached data, in seconds (optional; default is 600)
 * );
 */
$wgGadgetRepositories = array();

/**
 * Whether or not to allow gadgets to be shared in the gadget manager.
 * Note that this does not make it impossible for someone to load a gadget
 * from this wiki, it just removes the option from the interface for gadget managers,
 * and enforces the option when saving.
 */
$wgGadgetEnableSharing = true;

/*** Setup ***/

define( 'NS_GADGET', 2300 );
define( 'NS_GADGET_TALK', 2301 );
define( 'NS_GADGET_DEFINITION', 2302 );
define( 'NS_GADGET_DEFINITION_TALK', 2303 );

$wgNamespaceProtection[NS_GADGET][] = 'gadgets-edit';
$wgNamespaceProtection[NS_GADGET_DEFINITION][] = 'gadgets-definition-edit';

// Page titles in this namespace should match gadget ids,
// which historically may start both lowercase or uppercase.
$wgCapitalLinkOverrides[NS_GADGET_DEFINITION] = false;

$wgAvailableRights = array_merge( $wgAvailableRights, array(
	'gadgets-edit',
	'gadgets-definition-create',
	'gadgets-definition-edit',
	'gadgets-definition-delete'
) );

// Example of user groups
// Copy to your LocalSettings.php to use these
// or grant the rights to an existing group (e.g. sysops)
#$wgGroupPermissions['gadgetartists']['gadgets-edit'] = true;
#$wgGroupPermissions['gadgetmanagers']['gadgets-definition-create'] = true;
#$wgGroupPermissions['gadgetmanagers']['gadgets-definition-edit'] = true;
#$wgGroupPermissions['gadgetmanagers']['gadgets-definition-delete'] = true;

$wgHooks['AfterImportPage'][]               = 'GadgetsHooks::gadgetDefinitionImport';
$wgHooks['AfterImportPage'][]               = 'GadgetsHooks::cssOrJsPageImport';
$wgHooks['ArticleDeleteComplete'][]         = 'GadgetsHooks::gadgetDefinitionDelete';
$wgHooks['ArticleDeleteComplete'][]         = 'GadgetsHooks::cssJsPageDelete';
$wgHooks['ArticleSaveComplete'][]           = 'GadgetsHooks::gadgetDefinitionSave';
$wgHooks['ArticleSaveComplete'][]           = 'GadgetsHooks::cssOrJsPageSave';
$wgHooks['ArticleUndelete'][]               = 'GadgetsHooks::gadgetDefinitionUndelete';
$wgHooks['ArticleUndelete'][]               = 'GadgetsHooks::cssOrJsPageUndelete';
$wgHooks['BeforePageDisplay'][]             = 'GadgetsHooks::beforePageDisplay';
$wgHooks['MakeGlobalVariablesScript'][]     = 'GadgetsHooks::makeGlobalVariablesScript';
$wgHooks['CanonicalNamespaces'][]           = 'GadgetsHooks::canonicalNamespaces';
$wgHooks['GetPreferences'][]                = 'GadgetsHooks::getPreferences';
$wgHooks['UserGetDefaultOptions'][]         = 'GadgetsHooks::userGetDefaultOptions';
$wgHooks['LoadExtensionSchemaUpdates'][]    = 'GadgetsHooks::loadExtensionSchemaUpdates';
$wgHooks['PreferencesGetLegend'][]          = 'GadgetsHooks::preferencesGetLegend';
$wgHooks['ResourceLoaderRegisterModules'][] = 'GadgetsHooks::registerModules';
$wgHooks['TitleIsCssOrJsPage'][]            = 'GadgetsHooks::titleIsCssOrJsPage';
$wgHooks['TitleIsMovable'][]                = 'GadgetsHooks::titleIsMovable';
$wgHooks['TitleMoveComplete'][]             = 'GadgetsHooks::cssOrJsPageMove';
$wgHooks['getUserPermissionsErrors'][]      = 'GadgetsHooks::getUserPermissionsErrors';
$wgHooks['UnitTestsList'][]                 = 'GadgetsHooks::unitTestsList';

$dir = dirname(__FILE__) . '/';
$wgExtensionMessagesFiles['Gadgets'] = $dir . 'Gadgets.i18n.php';
$wgExtensionMessagesFiles['GadgetsNamespaces'] = $dir . 'Gadgets.namespaces.php';
$wgExtensionAliasesFiles['Gadgets'] = $dir . 'Gadgets.alias.php';

$wgAutoloadClasses['ApiQueryGadgetCategories'] = $dir . 'api/ApiQueryGadgetCategories.php';
$wgAutoloadClasses['ApiQueryGadgetPages'] = $dir . 'api/ApiQueryGadgetPages.php';
$wgAutoloadClasses['ApiQueryGadgets'] = $dir . 'api/ApiQueryGadgets.php';
$wgAutoloadClasses['ApiGetGadgetPrefs'] = $dir . 'api/ApiGetGadgetPrefs.php';
$wgAutoloadClasses['ApiSetGadgetPrefs'] = $dir . 'api/ApiSetGadgetPrefs.php';
$wgAutoloadClasses['CachedGadgetRepo'] = $dir . 'backend/CachedGadgetRepo.php';
$wgAutoloadClasses['ForeignAPIGadgetRepo'] = $dir . 'backend/ForeignAPIGadgetRepo.php';
$wgAutoloadClasses['ForeignDBGadgetRepo'] = $dir . 'backend/ForeignDBGadgetRepo.php';
$wgAutoloadClasses['Gadget'] = $dir . 'backend/Gadget.php';
$wgAutoloadClasses['GadgetsHooks'] = $dir . 'Gadgets.hooks.php';
$wgAutoloadClasses['GadgetPageList'] = $dir . 'backend/GadgetPageList.php';
$wgAutoloadClasses['GadgetRepo'] = $dir . 'backend/GadgetRepo.php';
$wgAutoloadClasses['GadgetResourceLoaderModule'] = $dir . 'backend/GadgetResourceLoaderModule.php';
$wgAutoloadClasses['GadgetOptionsResourceLoaderModule'] = $dir . 'backend/GadgetOptionsResourceLoaderModule.php';
$wgAutoloadClasses['GadgetPrefs'] = $dir . 'backend/GadgetPrefs.php';
$wgAutoloadClasses['LocalGadgetRepo'] = $dir . 'backend/LocalGadgetRepo.php';
$wgAutoloadClasses['MigrateGadgets'] = $dir . 'migrateGadgets.php';
$wgAutoloadClasses['PopulateGadgetPageList'] = $dir . 'populateGadgetPageList.php';
$wgAutoloadClasses['SpecialGadgets'] = $dir . 'SpecialGadgets.php';

$wgSpecialPages['Gadgets'] = 'SpecialGadgets';
$wgSpecialPageGroups['Gadgets'] = 'wiki';

$wgAPIListModules['gadgetcategories'] = 'ApiQueryGadgetCategories';
$wgAPIListModules['gadgets'] = 'ApiQueryGadgets';
$wgAPIListModules['gadgetpages'] = 'ApiQueryGadgetPages';

$wgAPIModules['setgadgetprefs'] = 'ApiSetGadgetPrefs';
$wgAPIModules['getgadgetprefs'] = 'ApiGetGadgetPrefs';

$gadResourceTemplate = array(
	'localBasePath' => $dir . 'modules',
	'remoteExtPath' => 'Gadgets/modules'
);
$wgResourceModules += array(
	// Styling for elements outputted by PHP
	'ext.gadgets.specialgadgets.prejs' => $gadResourceTemplate + array(
		'styles' => 'ext.gadgets.specialgadgets.prejs.css',
		'position' => 'top',
	),
	// Initializes mw.gadgets object
	'ext.gadgets.init' => $gadResourceTemplate + array(
		'scripts' => 'ext.gadgets.init.js',
		'position' => 'top',
	),
	// Adds tabs to Special:Gadgets
	'ext.gadgets.specialgadgets.tabs' => $gadResourceTemplate + array(
		'scripts' => 'ext.gadgets.specialgadgets.tabs.js',
		'messages' => array(
			'gadgets-gadget-create',
			'gadgets-gadget-create-tooltip',
		),
		'dependencies' => array(
			'ext.gadgets.init',
			'mediawiki.util',
		),
		'position' => 'top',
	),
	// Method to interact with API
	'ext.gadgets.api' => $gadResourceTemplate + array(
		'scripts' => 'ext.gadgets.api.js',
		// Can't depend on user.tokens yet due to a bug in ResourceLoader (bug 30914)
		'dependencies' => array(
			'ext.gadgets.init',
			'mediawiki.Title',
			'mediawiki.util',
			#'user.tokens',
		),
	),
	// jQuery plugin
	'jquery.createPropCloud' => $gadResourceTemplate + array(
		'scripts' => 'jquery.createPropCloud.js',
	),
	// Event handling, UI components, initiates on document ready
	'ext.gadgets.gadgetmanager' => $gadResourceTemplate + array(
		'scripts' => 'ext.gadgets.gadgetmanager.js',
		'styles' => 'ext.gadgets.gadgetmanager.css',
		'dependencies' => array(
			'ext.gadgets.init',
			'ext.gadgets.api',
			'jquery.localize',
			'jquery.ui.autocomplete',
			'jquery.ui.dialog',
			'jquery.createPropCloud',
			'jquery.json',
			'jquery.spinner',
			'mediawiki.Title',
			'mediawiki.util',
		),
		'messages' => array(
			'gadgetmanager-editor-title-editing',
			'gadgetmanager-editor-title-creating',
			'gadgetmanager-editor-prop-remove',
			'gadgetmanager-editor-removeprop-tooltip',
			'gadgetmanager-editor-save',
			'gadgetmanager-editor-cancel',
			'gadgetmanager-prop-id',
			'gadgetmanager-prop-id-error-blank',
			'gadgetmanager-prop-id-error-illegal',
			'gadgetmanager-prop-id-error-taken',
			'colon-separator',
			'gadgetmanager-propsgroup-settings',
			'gadgetmanager-propsgroup-module',
			'gadgetmanager-prop-scripts',
			'gadgetmanager-prop-styles',
			'gadgetmanager-prop-dependencies',
			'gadgetmanager-prop-messages',
			'gadgetmanager-prop-category',
			'gadgetmanager-prop-category-new',
			'gadgetmanager-prop-rights',
			'gadgetmanager-prop-default',
			'gadgetmanager-prop-hidden',
			'gadgetmanager-prop-shared',
			'gadgetmanager-comment-modify',
			'gadgetmanager-comment-create',
		),
	),
	'ext.gadgets.preferences' => $gadResourceTemplate + array(
		'scripts' => 'ext.gadgets.preferences.js',
		'dependencies' => 'ext.gadgets.api',
		'messages' => array(
			'gadgets-sharedprefs-ajaxerror'
		),
	),
	'ext.gadgets.preferences.style' => $gadResourceTemplate + array(
		'styles' => 'ext.gadgets.preferences.css',
	),
	'jquery.formBuilder' => $gadResourceTemplate + array(
		'scripts' => array( 'jquery.formBuilder.js' ),
		'styles' => array( 'jquery.formBuilder.css' ),
		'dependencies' => array(
			// TODO load some of this stuff on-demand
			'jquery.ui.slider', 'jquery.ui.datepicker', 'jquery.ui.position',
			'jquery.ui.draggable', 'jquery.ui.droppable', 'jquery.ui.sortable', 'jquery.ui.dialog',
			'jquery.ui.tabs', 'jquery.farbtastic', 'jquery.colorUtil', 'jquery.validate'
		),
		'messages' => array(
			'gadgets-formbuilder-required', 'gadgets-formbuilder-minlength', 'gadgets-formbuilder-maxlength',
			'gadgets-formbuilder-min', 'gadgets-formbuilder-max', 'gadgets-formbuilder-integer', 'gadgets-formbuilder-date',
			'gadgets-formbuilder-color', 'gadgets-formbuilder-list-required', 'gadgets-formbuilder-list-minlength',
			'gadgets-formbuilder-list-maxlength', 'gadgets-formbuilder-scalar',
			'gadgets-formbuilder-editor-ok', 'gadgets-formbuilder-editor-cancel', 'gadgets-formbuilder-editor-move-field',
			'gadgets-formbuilder-editor-delete-field', 'gadgets-formbuilder-editor-edit-field', 'gadgets-formbuilder-editor-edit-field-title', 'gadgets-formbuilder-editor-insert-field',
			'gadgets-formbuilder-editor-choose-field', 'gadgets-formbuilder-editor-choose-field-title', 'gadgets-formbuilder-editor-create-field-title',
			'gadgets-formbuilder-editor-duplicate-name', 'gadgets-formbuilder-editor-delete-section', 'gadgets-formbuilder-editor-new-section',
			'gadgets-formbuilder-editor-edit-section', 'gadgets-formbuilder-editor-choose-title', 'gadgets-formbuilder-editor-choose-title-title'
		),
	),
);
