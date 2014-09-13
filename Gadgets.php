<?php
/**
 * Gadgets extension - lets users select custom javascript gadgets
 *
 * For more info see http://mediawiki.org/wiki/Extension:Gadgets
 *
 * @file
 * @ingroup Extensions
 * @author Daniel Kinzler, brightbyte.de
 * @copyright © 2007 Daniel Kinzler
 * @license GNU General Public Licence 2.0 or later
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

if ( version_compare( $wgVersion, '1.24c', '<' ) ) { // Needs to be 1.24c because version_compare() works in confusing ways
	die( "This version of Extension:Gadgets requires MediaWiki 1.24+\n" );
}

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Gadgets',
	'author' => array( 'Daniel Kinzler', 'Max Semenik', 'Roan Kattouw', 'Timo Tijhof' ),
	'url' => 'https://www.mediawiki.org/wiki/Extension:Gadgets',
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
 * 	'apiUrl' => 'https://www.mediawiki.org/w/api.php',
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
$wgGadgetEnableSharing = false;

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

// Give all gadgets-* userrights to sysops by default
$wgGroupPermissions['sysop']['gadgets-edit'] = true;
$wgGroupPermissions['sysop']['gadgets-definition-create'] = true;
$wgGroupPermissions['sysop']['gadgets-definition-edit'] = true;
$wgGroupPermissions['sysop']['gadgets-definition-delete'] = true;

$wgHooks['AfterImportPage'][]               = 'GadgetsHooks::gadgetDefinitionImport';
$wgHooks['AfterImportPage'][]               = 'GadgetsHooks::cssOrJsPageImport';
$wgHooks['ArticleUndelete'][]               = 'GadgetsHooks::gadgetDefinitionUndelete';
$wgHooks['BeforePageDisplay'][]             = 'GadgetsHooks::onBeforePageDisplay';
$wgHooks['MakeGlobalVariablesScript'][]     = 'GadgetsHooks::onMakeGlobalVariablesScript';
$wgHooks['CanonicalNamespaces'][]           = 'GadgetsHooks::onCanonicalNamespaces';
$wgHooks['GetPreferences'][]                = 'GadgetsHooks::onGetPreferences';
$wgHooks['UserGetDefaultOptions'][]         = 'GadgetsHooks::onUserGetDefaultOptions';
$wgHooks['LoadExtensionSchemaUpdates'][]    = 'GadgetsHooks::onLoadExtensionSchemaUpdates';
$wgHooks['ParserTestTables'][]              = 'GadgetsHooks::onParserTestTables';
$wgHooks['PreferencesGetLegend'][]          = 'GadgetsHooks::onPreferencesGetLegend';
$wgHooks['ResourceLoaderRegisterModules'][] = 'GadgetsHooks::onResourceLoaderRegisterModules';
$wgHooks['TitleIsMovable'][]                = 'GadgetsHooks::onTitleIsMovable';
$wgHooks['TitleMoveComplete'][]             = 'GadgetsHooks::onTitleMoveComplete';
$wgHooks['getUserPermissionsErrors'][]      = 'GadgetsHooks::getUserPermissionsErrors';
$wgHooks['UnitTestsList'][]                 = 'GadgetsHooks::onUnitTestsList';
$wgHooks['ContentHandlerDefaultModelFor'][] = 'GadgetsHooks::onContentHandlerDefaultModelFor';
$wgExtensionFunctions[]                     = 'GadgetsHooks::addAPIMessageMapEntries';

# Extension:CodeEditor
$wgHooks['CodeEditorGetPageLanguage'][]     = 'GadgetsHooks::onCodeEditorGetPageLanguage';

$dir = dirname( __FILE__ ) . '/';
$wgMessagesDirs['Gadgets'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['Gadgets'] = $dir . 'Gadgets.i18n.php';
$wgExtensionMessagesFiles['GadgetsNamespaces'] = $dir . 'Gadgets.namespaces.php';
$wgExtensionMessagesFiles['GadgetsAlias'] = $dir . 'Gadgets.alias.php';

$wgAutoloadClasses['ApiQueryGadgetCategories'] = $dir . 'api/ApiQueryGadgetCategories.php';
$wgAutoloadClasses['ApiQueryGadgetPages'] = $dir . 'api/ApiQueryGadgetPages.php';
$wgAutoloadClasses['ApiQueryGadgets'] = $dir . 'api/ApiQueryGadgets.php';
$wgAutoloadClasses['CachedGadgetRepo'] = $dir . 'backend/CachedGadgetRepo.php';
$wgAutoloadClasses['ForeignAPIGadgetRepo'] = $dir . 'backend/ForeignAPIGadgetRepo.php';
$wgAutoloadClasses['ForeignDBGadgetRepo'] = $dir . 'backend/ForeignDBGadgetRepo.php';
$wgAutoloadClasses['Gadget'] = $dir . 'backend/Gadget.php';
$wgAutoloadClasses['GadgetsHooks'] = $dir . 'Gadgets.hooks.php';
$wgAutoloadClasses['GadgetPageList'] = $dir . 'backend/GadgetPageList.php';
$wgAutoloadClasses['GadgetRepo'] = $dir . 'backend/GadgetRepo.php';
$wgAutoloadClasses['GadgetRepoFactory'] = $dir . 'backend/GadgetRepoFactory.php';
$wgAutoloadClasses['GadgetResourceLoaderModule'] = $dir . 'backend/GadgetResourceLoaderModule.php';
$wgAutoloadClasses['LocalGadgetRepo'] = $dir . 'backend/LocalGadgetRepo.php';
$wgAutoloadClasses['MigrateGadgets'] = $dir . 'migrateGadgets.php';
$wgAutoloadClasses['PopulateGadgetPageList'] = $dir . 'populateGadgetPageList.php';
$wgAutoloadClasses['SpecialGadgets'] = $dir . 'SpecialGadgets.php';

# content/
$wgAutoloadClasses['GadgetDefinitionContent'] = __DIR__ . '/content/GadgetDefinitionContent.php';
$wgAutoloadClasses['GadgetDefinitionContentHandler'] = __DIR__ . '/content/GadgetDefinitionContentHandler.php';
$wgAutoloadClasses['GadgetCssContent'] = __DIR__ . '/content/GadgetCssContent.php';
$wgAutoloadClasses['GadgetCssContentHandler'] = __DIR__ . '/content/GadgetCssContentHandler.php';
$wgAutoloadClasses['GadgetJsContent'] = __DIR__ . '/content/GadgetJsContent.php';
$wgAutoloadClasses['GadgetJsContentHandler'] = __DIR__ . '/content/GadgetJsContentHandler.php';
$wgAutoloadClasses['GadgetScriptDeletionUpdate'] = __DIR__ . '/content/GadgetScriptDeletionUpdate.php';
$wgAutoloadClasses['GadgetScriptSecondaryDataUpdate'] = __DIR__ . '/content/GadgetScriptSecondaryDataUpdate.php';

$wgAutoloadClasses['GadgetDefinitionDeletionUpdate'] = __DIR__ . '/content/GadgetDefinitionDeletionUpdate.php';
$wgAutoloadClasses['GadgetDefinitionSecondaryDataUpdate'] = __DIR__ . '/content/GadgetDefinitionSecondaryDataUpdate.php';

# tests/
$wgAutoloadClasses['GadgetContentTestCase'] = __DIR__ . '/tests/content/GadgetContentTestCase.php';

$wgContentHandlers['GadgetDefinition'] = 'GadgetDefinitionContentHandler';
$wgContentHandlers['GadgetCss'] = 'GadgetCssContentHandler';
$wgContentHandlers['GadgetJs'] = 'GadgetJsContentHandler';
$wgNamespaceContentModels[NS_GADGET_DEFINITION] = 'GadgetDefinition';

$wgSpecialPages['Gadgets'] = 'SpecialGadgets';
$wgSpecialPageGroups['Gadgets'] = 'wiki';

$wgAPIListModules['gadgetcategories'] = 'ApiQueryGadgetCategories';
$wgAPIListModules['gadgets'] = 'ApiQueryGadgets';
$wgAPIListModules['gadgetpages'] = 'ApiQueryGadgetPages';

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
		'dependencies' => array(
			'ext.gadgets.init',
			'mediawiki.Title',
			'mediawiki.util',
			'mediawiki.api',
			'user.tokens',
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
			'mediawiki.api',
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
			'gadgetmanager-prop-scripts-placeholder',
			'gadgetmanager-prop-styles',
			'gadgetmanager-prop-styles-placeholder',
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
			'gadgets-sharedprefs-ajaxerror',
			'gadgets-preference-description'
		),
	),
	'ext.gadgets.preferences.style' => $gadResourceTemplate + array(
		'styles' => 'ext.gadgets.preferences.css',
	),
);
