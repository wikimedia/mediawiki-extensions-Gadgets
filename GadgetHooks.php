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
	 * ArticleDeleteComplete hook handler.
	 * 
	 * @param $article Article
	 * @param $user User
	 * @param $reason String: Deletion summary
	 * @param $id Int: Page ID
	 */
	public static function articleDeleteComplete( $article, $user, $reason, $id ) {
		// FIXME: AARGH, duplication, refactor this
		$title = $article->getTitle();
		$name = $title->getText();
		// Check that the deletion is in the Gadget definition: namespace and that the name ends in .js
		if ( $title->getNamespace() !== NS_GADGET_DEFINITION || !preg_match( '!\.js$!u', $name ) ) {
			return true;
		}
		// Trim .js from the page name to obtain the gadget name
		$name = substr( $name, 0, -3 );
		
		$repo = new LocalGadgetRepo( array() );
		$repo->deleteGadget( $name );
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
	public static function articleSaveComplete( $article, $user, $text, $summary, $isMinor,
			$isWatch, $section, $flags, $revision )
	{
		$title = $article->getTitle();
		$name = $title->getText();
		// Check that the edit is in the Gadget definition: namespace, that the name ends in .js
		// and that $revision isn't null (this happens for a no-op edit)
		if ( $title->getNamespace() !== NS_GADGET_DEFINITION || !preg_match( '!\.js$!u', $name ) || !$revision ) {
			return true;
		}
		// Trim .js from the page name to obtain the gadget name
		$name = substr( $name, 0, -3 );
		
		$previousRev = $revision->getPrevious();
		$prevTs = $previousRev instanceof Revision ? $previousRev->getTimestamp() : wfTimestampNow();
		
		// Update the database entry for this gadget
		$repo = new LocalGadgetRepo( array() );
		// TODO: Timestamp in the constructor is ugly
		$gadget = new Gadget( $name, $repo, $text, $prevTs );
		$repo->modifyGadget( $gadget, $revision->getTimestamp() );
		
		// modifyGadget() returns a Status object with an error if there was a conflict,
		// but we don't care. If a conflict occurred, that must be because a newer edit's
		// DB update occurred before ours, in which case the right thing to do is to occu
		
		return true;
	}
	
	public static function articleUndelete( $title, $created, $comment ) {
		// FIXME: AARGH, duplication, refactor this
		$name = $title->getText();
		// Check that the deletion is in the Gadget definition: namespace and that the name ends in .js
		if ( $title->getNamespace() !== NS_GADGET_DEFINITION || !preg_match( '!\.js$!u', $name ) ) {
			return true;
		}
		// Trim .js from the page name to obtain the gadget name
		$name = substr( $name, 0, -3 );
		
		// Check whether this undeletion changed the latest revision of the page, by comparing
		// the timestamp of the latest revision with the timestamp in the DB
		$repo = new LocalGadgetRepo( array() );
		$gadget = $repo->getGadget( $name );
		$gadgetTS = $gadget ? $gadget->getTimestamp() : 0;
		
		$rev = Revision::newFromTitle( $title );
		if ( wfTimestamp( TS_MW, $rev->getTimestamp() ) ===
				wfTimestamp( TS_MW, $gadgetTS ) ) {
			// The latest rev didn't change. Someone must've undeleted an older revision
			return true;
		}
		
		// Update the database entry for this gadget
		$newGadget = new Gadget( $name, $repo, $rev->getRawText(), $gadgetTS );
		$repo->modifyGadget( $newGadget, $rev->getTimestamp() );
		
		// modifyGadget() returns a Status object with an error if there was a conflict,
		// but we do't care, see similar comment in articleSaveComplete()
		return true;
	}

	/**
	 * GetPreferences hook handler.
	 * @param $user User
	 * @param $preferences Array: Preference descriptions
	 */
	public static function getPreferences( $user, &$preferences ) {
		// TODO: Part of this is duplicated from registerModules(), factor out into the repo
		$repo = new LocalGadgetRepo( array() );
		
		$gadgets = $repo->getGadgetNames();
		$categories = array(); // array( category => array( desc => name ) )
		$default = array(); // array of Gadget names
		foreach ( $gadgets as $name ) {
			$gadget = $repo->getGadget( $name );
			if ( !$gadget->isAllowed( $user ) || $gadget->isHidden() ) {
				continue;
			}
			$category = $gadget->getCategory();
			
			// Add the Gadget to the right category
			$description = wfMessage( $gadget->getDescriptionMsg() )->parse();
			$categories[$category][$description] = $name;
			// Add the Gadget to the default list if enabled
			if ( $gadget->isEnabledForUser( $user ) ) {
				$default[] = $name;
			}
		}
		
		$options = array(); // array( desc1 => name1, category1 => array( desc2 => name2 ) )
		foreach ( $categories as $category => $gadgets ) {
			if ( $category !== '' ) {
				$categoryMsg = wfMsgExt( "gadgetcategory-$category", 'parseinline' );
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
		return true;
	}

	/**
	 * ResourceLoaderRegisterModules hook handler.
	 * @param $resourceLoader ResourceLoader
	 */
	public static function registerModules( &$resourceLoader ) {
		global $wgGadgetRepositories;
		foreach ( $wgGadgetRepositories as $params ) {
			$repoClass = $params['class'];
			unset( $params['class'] );
			$repo = new $repoClass( $params );
			
			$gadgets = $repo->getGadgetNames();
			foreach ( $gadgets as $name ) {
				$gadget = $repo->getGadget( $name );
				$resourceLoader->register( $gadget->getModuleName(), $gadget->getModule() );
			}
		}
		return true;
	}

	/**
	 * BeforePageDisplay hook handler.
	 * @param $out OutputPage
	 */
	public static function beforePageDisplay( $out ) {
		global $wgUser, $wgGadgetRepositories;
		
		wfProfileIn( __METHOD__ );
		
		foreach ( $wgGadgetRepositories as $params ) {
			$repoClass = $params['class'];
			unset( $params['class'] );
			$repo = new $repoClass( $params );
			
			$gadgets = $repo->getGadgetNames();
			foreach ( $gadgets as $name ) {
				$gadget = $repo->getGadget( $name );
				if ( $gadget->isEnabledForUser( $wgUser ) && $gadget->isAllowed( $wgUser ) ) {
					$out->addModules( $gadget->getModuleName() );
				}
			}
		}
		
		wfProfileOut( __METHOD__ );
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
