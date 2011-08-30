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
	 * ArticleSaveComplete hook handler.
	 * 
	 * @param $article Article
	 * @param $user User
	 * @param $text String: New page text
	 */
	public static function articleSaveComplete( $article, $user, $text ) {
		//update cache if MediaWiki:Gadgets-definition was edited
		$title = $article->mTitle;
		if( $title->getNamespace() == NS_MEDIAWIKI && $title->getText() == 'Gadgets-definition' ) {
			Gadget::loadStructuredList( $text );
		}
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
		if ( $title->getNamespace() == NS_GADGET ) {
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
