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

class GadgetsHooks {
	/**
	 * Get the gadget ID from a title
	 * @param $title Title object
	 * @return string Gadget id or null if not a gadget definition page
	 */
	public static function getIDFromTitle( Title $title ) {
		$id = $title->getText();
		if ( $title->getNamespace() !== NS_GADGET_DEFINITION || !preg_match( '!\.js$!u', $id ) ) {
			// Not a gadget definition page
			return null;
		}
		// Trim .js from the page name to obtain the gadget ID
		return substr( $id, 0, -3 );
	}

	/**
	 * Get a Title object of the gadget definition page from a gadget id
	 * @param $id String
	 * @return Title|null
	 */
	public static function getDefinitionTitleFromID( $id ) {
		return Title::makeTitleSafe( NS_GADGET_DEFINITION, $id . '.js' );
	}

	/**
	 * ArticleDeleteComplete hook handler.
	 *
	 * @param $article Article
	 * @param $user User
	 * @param $reason String: Deletion summary
	 * @param $id Int: Page ID
	 */
	public static function gadgetDefinitionDelete( $article, $user, $reason, $id ) {
		$id = self::getIDFromTitle( $article->getTitle() );
		if ( !$id ) {
			return true;
		}

		$repo = LocalGadgetRepo::singleton();
		$repo->deleteGadget( $id );
		// deleteGadget() may return an error if the Gadget didn't exist, but we don't care here
		return true;
	}

	/**
	 * ArticleSaveComplete hook handler.
	 *
	 * @param $article Article
	 * @param $user User
	 * @param $text String: New page text
	 * @param $summary String: Edit summary
	 * @param $isMinor Bool: Whether this was a minor edit
	 * @param $isWatch unused
	 * @param $section unused
	 * @param $flags: Int: Bitmap of flags passed to WikiPage::doEdit()
	 * @param $revision: Revision object for the new revision
	 */
	public static function gadgetDefinitionSave( $article, $user, $text, $summary, $isMinor,
			$isWatch, $section, $flags, $revision )
	{
		$id = self::getIDFromTitle( $article->getTitle() );
		if ( !$id ) {
			return true;
		}

		$prevTs = wfTimestampNow();
		$thisTs = wfTimestampNow();
		if ( $revision ) { // $revision is null for null edits
			$thisTs = $revision->getTimestamp();
			$previousRev = $revision->getPrevious();
			if ( $previousRev ) { // $previousRev is null if there is no previous revision
				$prevTs = $previousRev->getTimestamp();
			}
		}

		// Update the database entry for this gadget
		$repo = LocalGadgetRepo::singleton();
		// TODO: Timestamp in the constructor is ugly
		$gadget = new Gadget( $id, $repo, $text, $prevTs );
		$repo->modifyGadget( $gadget, $thisTs );

		// modifyGadget() returns a Status object with an error if there was a conflict,
		// but we don't care. If a conflict occurred, that must be because a newer edit's
		// DB update occurred before ours, in which case the right thing to do is to occu

		return true;
	}

	/**
	 * Update the database entry for a gadget if the description page is
	 * newer than the database entry.
	 * @param $title Title object
	 */
	public static function gadgetDefinitionUpdateIfChanged( $title ) {
		$id = self::getIDFromTitle( $title );
		if ( !$id ) {
			return;
		}

		// Check whether this undeletion changed the latest revision of the page, by comparing
		// the timestamp of the latest revision with the timestamp in the DB
		$repo = LocalGadgetRepo::singleton();
		$gadget = $repo->getGadget( $id );
		$gadgetTS = $gadget ? $gadget->getTimestamp() : 0;

		$rev = Revision::newFromTitle( $title );
		if ( wfTimestamp( TS_MW, $rev->getTimestamp() ) ===
				wfTimestamp( TS_MW, $gadgetTS ) ) {
			// The latest rev didn't change. Someone must've undeleted an older revision
			return;
		}

		// Update the database entry for this gadget
		$newGadget = new Gadget( $id, $repo, $rev->getRawText(), $gadgetTS );
		$repo->modifyGadget( $newGadget, $rev->getTimestamp() );

		// modifyGadget() returns a Status object with an error if there was a conflict,
		// but we do't care, see similar comment in articleSaveComplete()
		return;
	}

	/**
	 * ArticleUndelete hook handler
	 * @param $title Title object
	 * @param $created Bool: Whether this undeletion recreated the page
	 * @param $comment String: Undeletion summary
	 */
	public static function gadgetDefinitionUndelete( $title, $created, $comment ) {
		self::gadgetDefinitionUpdateIfChanged( $title );
		return true;
	}

	public static function gadgetDefinitionImport( $title, $origTitle, $revCount, $sRevCount, $pageInfo ) {
		self::gadgetDefinitionUpdateIfChanged( $title );
		return true;
	}

	/**
	 * ArticleDeleteComplete hook handler.
	 *
	 * @param $article Article
	 * @param $user User
	 * @param $reason String: Deletion summary
	 * @param $id Int: Page ID
	 */
	public static function cssJsPageDelete( $article, $user, $reason, $id ) {
		GadgetPageList::delete( $article->getTitle() );
		return true;
	}

	/**
	 * ArticleSaveComplete hook handler.
	 *
	 * @param $article Article
	 * @param $user User
	 * @param $text String: New page text
	 * @param $summary String: Edit summary
	 * @param $isMinor Bool: Whether this was a minor edit
	 * @param $isWatch unused
	 * @param $section unused
	 * @param $flags: Int: Bitmap of flags passed to WikiPage::doEdit()
	 * @param $revision: Revision object for the new revision
	 */
	public static function cssOrJsPageSave( $article, $user, $text, $summary, $isMinor,
			$isWatch, $section, $flags, $revision )
	{
		$title = $article->getTitle();
		GadgetPageList::updatePageStatus( $title );
		return true;
	}

	/**
	 * ArticleUndelete hook handler
	 * @param $title Title object
	 * @param $created Bool: Whether this undeletion recreated the page
	 * @param $comment String: Undeletion summary
	 */
	public static function cssOrJsPageUndelete( $title, $created, $comment ) {
		GadgetPageList::updatePageStatus( $title );
		return true;
	}

	public static function cssOrJsPageMove( $oldTitle, $newTitle, $user, $pageid, $redirid ) {
		// Delete the old title from the list. Even if it still exists after the move,
		// it'll be a redirect and we don't want those in there
		GadgetPageList::delete( $oldTitle );

		GadgetPageList::updatePageStatus( $newTitle );
		return true;
	}
	
	public static function cssOrJsPageImport( $title, $origTitle, $revCount, $sRevCount, $pageInfo ) {
		GadgetPageList::updatePageStatus( $title );
		return true;
	}

	/**
	 * UserGetDefaultOptions hook handler
	 * @param $defaultOptions Array of default preference keys and values
	 */
	public static function userGetDefaultOptions( &$defaultOptions ) {
		// Cache the stuff we're adding to $defaultOptions
		// This is done because this hook function is called dozens of times during a typical request
		// but we only want to hit the repo backend once
		static $add = null;
		if ( $add === null ) {
			$add = array();
			$repo = LocalGadgetRepo::singleton();
			$gadgetIds = $repo->getGadgetIds();
			foreach ( $gadgetIds as $gadgetId ) {
				$gadget = $repo->getGadget( $gadgetId );
				if ( $gadget->isEnabledByDefault() ) {
					$add['gadget-' . $gadget->getId()] = 1;
				}
			}
		}
		
		$defaultOptions = $add + $defaultOptions;
		return true;
	}

	/**
	 * GetPreferences hook handler.
	 * @param $user User
	 * @param $preferences Array: Preference descriptions
	 */
	public static function getPreferences( $user, &$preferences ) {
		// Add tab for local gadgets
		$preferences['gadgets-intro'] =
			array(
				'type' => 'info',
				'label' => '&#160;',
				'default' => Xml::tags( 'tr', array(),
					Xml::tags( 'td', array( 'colspan' => 2 ),
						wfMsgExt( 'gadgets-prefstext', 'parse' ) ) ),
				'section' => 'gadgets',
				'raw' => 1,
				'rawrow' => 1,
			);

		// Add tab for shared gadgets
		$preferences['gadgets-intro-shared'] =
			array(
				'type' => 'info',
				'label' => '&#160;',
				'default' => Xml::tags( 'tr', array(),
					Xml::tags( 'td', array( 'colspan' => 2 ),
						wfMsgExt( 'gadgets-sharedprefstext', 'parse' ) ) ),
				'section' => 'gadgetsshared',
				'raw' => 1,
				'rawrow' => 1,
			);
		
		// This loop adds the preferences for all gadgets, both local and remote
		// We want to use gadget IDs in HTMLForm IDs and repo and category IDs
		// in section IDs, but because certain characters are restricted
		// (HTMLForm will barf on anything that's not a valid HTML ID, section IDs will
		// get confused when dashes or slashes are added), we encode these things as hex
		// so we know for sure they don't contain weird characters and are easy to decode
		$repos = GadgetRepo::getAllRepos();
		foreach ( $repos as $repo ) {
			$encRepoSource = bin2hex( $repo->getSource() );
			$byCategory = $repo->getGadgetsByCategory();
			ksort( $byCategory );
			foreach ( $byCategory as $category => $gadgets ) {
				$encCategory = bin2hex( $category );
				foreach ( $gadgets as $gadget ) {
					$id = $gadget->getId();
					if ( $repo->isLocal() ) {
						// For local gadgets we have all the information
						$title = htmlspecialchars( $gadget->getTitleMessage() );
						$description = $gadget->getDescriptionMessage(); // Is parsed, doesn't need escaping
						if ( $description === '' ) {
							// Empty description, just use the title
							$text = $title;
						} else {
							$text = wfMessage( 'gadgets-preference-description' )->rawParams( $title, $description )->parse();
						}
						$sectionCat = $category === '' ? '' : "/gadgetcategorylocal-$encRepoSource-$encCategory";
						$preferences["gadget-$id"] = array(
							'type' => 'toggle',
							'label' => $text,
							'section' => "gadgets$sectionCat",
							'default' => $gadget->isEnabledForUser( $user ),
							'name' => 'gadgetpref-' . bin2hex( $id ),
						);
					} else {
						$sectionCat = $category === '' ? '' : "/gadgetcategory-$encRepoSource-$encCategory";
						$preferences["gadget-$id"] = array(
							'type' => 'toggle',
							'label' => htmlspecialchars( $id ), // will be changed by JS
							'section' => "gadgetsshared/gadgetrepo-$encRepoSource$sectionCat",
							'cssclass' => 'mw-gadgets-shared-pref',
							'name' => 'gadgetpref-' . bin2hex( $id ),
							// 'default' isn't in here by design: we don't want 
							// enabledByDefault to be honored across wikis
						);
					}
				}
			}
		}
		
		return true;
	}
	
	public static function preferencesGetLegend( $form, $key, &$legend ) {
		$matches = null;
		if ( preg_match( '/^(?:gadgetrepo|gadgetcategory(local)?-[A-Za-z0-9]*)-([A-Za-z0-9]*)$/', $key, $matches ) ) {
			// Decode the category or repo ID
			$id = pack( "H*", $matches[2] ); // PHP doesn't have hex2bin() yet
			if ( $matches[1] !== null ) {
				// This is a local category ID
				// We have access to the message, so display it
				$legend = LocalGadgetRepo::singleton()->getCategoryTitle( $id, $form->getLang() );
			} else {
				// This is a repository ID or a foreign category ID
				// Just display the ID itself (with ucfirst applied)
				// This will be changed to a properly i18ned string by JS
				$legend = $form->getLang()->ucfirst( $id );
			}
		}
		return true;
	}

	/**
	 * ResourceLoaderRegisterModules hook handler.
	 * @param $resourceLoader ResourceLoader
	 */
	public static function registerModules( &$resourceLoader ) {
		$gadgets = GadgetRepo::getAllGadgets();
		foreach ( $gadgets as $gadget ) {
			$resourceLoader->register( $gadget->getModuleName(), $gadget->getModule() );
		}
		return true;
	}

	/**
	 * BeforePageDisplay hook handler.
	 * @param $out OutputPage
	 */
	public static function beforePageDisplay( $out ) {
		wfProfileIn( __METHOD__ );

		$user = $out->getUser();
		$gadgets = GadgetRepo::getAllGadgets();
		foreach ( $gadgets as $gadget ) {
			if ( $gadget->isEnabledForUser( $user ) && $gadget->isAllowed( $user ) ) {
				$out->addModules( $gadget->getModuleName() );
			}
		}

		// Add preferences JS if we're on Special:Preferences
		if ( $out->getTitle()->isSpecial( 'Preferences' ) ) {
			$out->addModules( 'ext.gadgets.preferences' );
			$out->addModuleStyles( 'ext.gadgets.preferences.style' );
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/**
	 * MakeGlobalVariablesScript hook handler
	 * @param $vars Array: Key/value pars for mw.config.set on this page.
	 * @param $out OutputPage
	 */
	public static function makeGlobalVariablesScript( &$vars, $out ) {
		$title = $out->getTitle();
		$user = $out->getUser();
		// FIXME: This is not a nice way to do it. Maybe we should check for the presence
		// of a module instead or something.
		if ( $title->isSpecial( 'Gadgets' ) || $title->isSpecial( 'Preferences' ) ) {
			global $wgGadgetEnableSharing;

			// Pass the source data for each source that is used by a repository
			$repos = GadgetRepo::getAllRepos();
			$sources = $out->getResourceLoader()->getSources();
			$repoData = array();
			foreach ( $repos as $repo ) {
				$repoData[$repo->getSource()] = $sources[$repo->getSource()];
			}

			$vars['gadgetsConf'] = array(
				'enableSharing' => $wgGadgetEnableSharing,
				'allRights' => User::getAllRights(),
				'repos' => $repoData,
				'userIsAllowed' => array(
					'editinterface' => $user->isAllowed( 'editinterface' ),
					'gadgets-definition-create' => $user->isAllowed( 'gadgets-definition-create' ),
					'gadgets-definition-edit' => $user->isAllowed( 'gadgets-definition-edit' ),
					'gadgets-definition-delete' => $user->isAllowed( 'gadgets-definition-delete' ),
				),
			);
		}
		return true;
	}

	/**
	 * UnitTestsList hook handler
	 * @param $files Array: List of extension test files
	 */
	public static function unitTestsList( &$files ) {
		//$files[] = dirname( __FILE__ ) . '/tests/GadgetsTest.php'; //FIXME broken
		$files[] = dirname( __FILE__ ) . '/tests/GadgetPrefsTest.php';
		$files[] = dirname( __FILE__ ) . '/tests/GadgetTest.php';
		return true;
	}

	public static function loadExtensionSchemaUpdates( $updater ) {
		$dir = dirname( __FILE__ );
		$updater->addExtensionUpdate( array( 'addtable', 'gadgets', "$dir/sql/gadgets.sql", true ) );
		$updater->addExtensionUpdate( array( 'addtable', 'gadgetpagelist', "$dir/sql/patch-gadgetpagelist.sql", true ) );
		$updater->addPostDatabaseUpdateMaintenance( 'MigrateGadgets' );
		$updater->addPostDatabaseUpdateMaintenance( 'PopulateGadgetPageList' );
		return true;
	}

	public static function canonicalNamespaces( &$list ) {
		$list[NS_GADGET] = 'Gadget';
		$list[NS_GADGET_TALK] = 'Gadget_talk';
		$list[NS_GADGET_DEFINITION] = 'Gadget_definition';
		$list[NS_GADGET_DEFINITION_TALK] = 'Gadget_definition_talk';
		return true;
	}

	public static function titleIsCssOrJsPage( $title, &$result ) {
		if ( ( $title->getNamespace() == NS_GADGET || $title->getNamespace() == NS_GADGET_DEFINITION ) &&
				preg_match( '!\.(css|js)$!u', $title->getText() ) )
		{
			$result = true;
		}
		return true;
	}

	public static function titleIsMovable( $title, &$result ) {
		if ( $title->getNamespace() == NS_GADGET_DEFINITION ) {
			$result = false;
		}
		return true;
	}

	public static function getUserPermissionsErrors( $title, $user, $action, &$result ) {
		if ( $title->getNamespace() == NS_GADGET_DEFINITION ) {
			// Enforce restrictions on the Gadget_definition namespace
			if ( $action == 'create' && !$user->isAllowed( 'gadgets-definition-create' ) ) {
				$result[] = array( 'gadgets-cant-create' );
				return false;
			} elseif ( $action == 'delete' && !$user->isAllowed( 'gadgets-definition-delete' ) ) {
				$result[] = array( 'gadgets-cant-delete' );
				return false;
			}
		}
		return true;
	}
}
