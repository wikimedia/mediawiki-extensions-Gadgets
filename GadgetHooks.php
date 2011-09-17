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

class GadgetHooks {
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
		
		$previousRev = $revision->getPrevious();
		$prevTs = $previousRev instanceof Revision ? $previousRev->getTimestamp() : wfTimestampNow();
		
		// Update the database entry for this gadget
		$repo = LocalGadgetRepo::singleton();
		// TODO: Timestamp in the constructor is ugly
		$gadget = new Gadget( $id, $repo, $text, $prevTs );
		$repo->modifyGadget( $gadget, $revision->getTimestamp() );
		
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
	public static function cssOrJsPageSave(  $article, $user, $text, $summary, $isMinor,
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

	/**
	 * UserGetDefaultOptions hook handler
	 * @param $defaultOptions Array of default preference keys and values
	 */
	public static function userGetDefaultOptions( &$defaultOptions ) {
		$repo = LocalGadgetRepo::singleton();
		$gadgetIds = $repo->getGadgetIds();
		foreach ( $gadgetIds as $gadgetId ) {
			$gadget = $repo->getGadget( $gadgetId );
			if ( $gadget->isEnabledByDefault() ) {
				$defaultOptions['gadget-' . $gadget->getId()] = 1;
			}
		}
		return true;
	}

	/**
	 * GetPreferences hook handler.
	 * @param $user User
	 * @param $preferences Array: Preference descriptions
	 */
	public static function getPreferences( $user, &$preferences ) {
		$repo = LocalGadgetRepo::singleton();
		$gadgets = $repo->getGadgetIds();
		$categories = array(); // array( category => array( desc => title ) )
		$default = array(); // array of Gadget ids
		foreach ( $gadgets as $id ) {
			$gadget = $repo->getGadget( $id );
			if ( !$gadget->isAllowed( $user ) || $gadget->isHidden() ) {
				continue;
			}
			$category = $gadget->getCategory();
			
			// Add the Gadget to the right category
			$title = htmlspecialchars( $gadget->getTitleMessage() );
			$description = $gadget->getDescriptionMessage(); // Is parsed, doesn't need escaping
			if ( $description === '' ) {
				// Empty description, just use the title
				$text = $title;
			} else {
				$text = wfMessage( 'gadgets-preference-description' )->rawParams( $title, $description )->parse();
			}
			$categories[$category][$text] = $id;
			// Add the Gadget to the default list if enabled
			if ( $gadget->isEnabledForUser( $user ) ) {
				$default[] = $id;
			}
		}
		
		$options = array(); // array( desc1 => gadget1, category1 => array( desc2 => gadget2 ) )
		foreach ( $categories as $category => $gadgets ) {
			if ( $category !== '' ) {
				$categoryMsg = htmlspecialchars( $repo->getCategoryTitle( $category ) );
				$options[$categoryMsg] = $gadgets;
			} else {
				$options += $gadgets;
			}
		}
		
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
		$preferences['gadgets'] = 
			array(
				'type' => 'multiselect',
				'options' => $options,
				'section' => 'gadgets',
				'label' => '&#160;',
				'prefix' => 'gadget-',
				'default' => $default,
			);
		
		// Add tab for shared gadgets
		$preferences['gadgets-intro-shared'] =
			array(
				'type' => 'info',
				'label' => '&#160;',
				'default' => Xml::tags( 'tr', array(),
					Xml::tags( 'td', array( 'colspan' => 2 ),
						wfMsgExt( 'gadgets-sharedprefstext', 'parse' ) ) ),
				'section' => 'gadgets-shared',
				'raw' => 1,
				'rawrow' => 1,
			);
		$preferences['gadgets-shared'] =
			array(
				'type' => 'multiselect',
				'options' => array(), // TODO: Maybe fill in stuff anyway? The backend may need that
				'section' => 'gadgets-shared',
				'label' => '&#160;',
				'prefix' => 'gadget-',
				'default' => array(),
			);
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
		if ( $out->getTitle()->equals( SpecialPage::getTitleFor( 'Preferences' ) ) ) {
			$out->addModules( 'ext.gadgets.preferences' );
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
		// FIXME: This is not a nice way to do it. Maybe we should check for the presence
		// of a module instead or something.
		if ( $title->equals( SpecialPage::getTitleFor( 'GadgetManager' ) ) ||
				$title->equals( SpecialPage::getTitleFor( 'Preferences' ) ) )
		{
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
				'repos' => $repoData
			);
		}
		return true;
	}

	/**
	 * UnitTestsList hook handler
	 * @param $files Array: List of extension test files
	 */
	public static function unitTestsList( &$files ) {
		$files[] = dirname( __FILE__ ) . '/tests/GadgetsTest.php';
		return true;
	}

	public static function loadExtensionSchemaUpdates( $updater ) {
		$dir = dirname( __FILE__ );
		$updater->addExtensionUpdate( array( 'addtable', 'gadgets', "$dir/sql/gadgets.sql", true ) );
		$updater->addExtensionUpdate( array( 'addtable', 'gadgetpagelist', "$dir/sql/patch-gadgetpagelist.sql", true ) );
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
