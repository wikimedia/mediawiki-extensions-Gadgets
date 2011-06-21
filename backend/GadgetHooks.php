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
		//or if a Mediawiki:Gadget-foo.preferences was edited
		$title = $article->mTitle;
		if( $title->getNamespace() == NS_MEDIAWIKI ) {
			if ( $title->getText() == 'Gadgets-definition'
				|| preg_match( '/Gadget-([a-zA-Z](?:[-_:.\w\d ]*[a-zA-Z0-9])?)-config/', $title->getText() ) )
			{
				Gadget::loadStructuredList( $text );
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
		$gadgets = Gadget::loadStructuredList();
		if (!$gadgets) return true;
		
		$options = array();
		$default = array();
		foreach( $gadgets as $section => $thisSection ) {
			$available = array();
			foreach( $thisSection as $gadget ) {
				if ( $gadget->isAllowed( $user ) ) {
					$gname = $gadget->getName();
					$available[$gadget->getDescription()] = $gname;
					if ( $gadget->isEnabled( $user ) ) {
						$default[] = $gname;
					}
				}
			}
			if ( $section !== '' ) {
				$section = wfMessage( "gadget-section-$section" )->parse();
				if ( count ( $available ) ) {
					$options[$section] = $available;
				}
			} else {
				$options = array_merge( $options, $available );
			}
		}
		
		$preferences['gadgets-intro'] =
			array(
				'type' => 'info',
				'label' => '&#160;',
				'default' => Xml::tags( 'tr', array(),
					Xml::tags( 'td', array( 'colspan' => 2 ),
						wfMessage( 'gadgets-prefstext' )->parse() ) ),
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
		$gadgets = Gadget::loadList();
		if ( !$gadgets ) {
			return true;
		}
		foreach ( $gadgets as $g ) {
			$module = $g->getModule();
			if ( $module ) {
				$resourceLoader->register( $g->getModuleName(), $module );
			}
		}
		return true;
	}

	/**
	 * BeforePageDisplay hook handler.
	 * @param $out OutputPage
	 */
	public static function beforePageDisplay( $out ) {
		global $wgUser;
		
		wfProfileIn( __METHOD__ );

		//tweaks in Special:Preferences
		if ( $out->getTitle()->isSpecial( 'Preferences' ) ) {
			$out->addModules( 'ext.gadgets.preferences' );
		}

		$gadgets = Gadget::loadList();
		if ( !$gadgets ) {
			wfProfileOut( __METHOD__ );
			return true;
		}

		$lb = new LinkBatch();
		$lb->setCaller( __METHOD__ );
		$pages = array();

		foreach ( $gadgets as $gadget ) {
			if ( $gadget->isEnabled( $wgUser ) && $gadget->isAllowed( $wgUser ) ) {
				if ( $gadget->hasModule() ) {
					$out->addModules( $gadget->getModuleName() );
				}
				foreach ( $gadget->getLegacyScripts() as $page ) {
					$lb->add( NS_MEDIAWIKI, $page );
					$pages[] = $page;
				}
			}
		}

		$lb->execute( __METHOD__ );

		$done = array();
		foreach ( $pages as $page ) {
			if ( isset( $done[$page] ) ) continue;
			$done[$page] = true;
			self::applyScript( $page, $out );
		}
		wfProfileOut( __METHOD__ );

		return true;
	}

	/**
	 * UserLoadOptions hook handler.
	 * @param $user
	 * @param &$options 
	 */
	public static function userLoadOptions( $user, &$options ) {
		
		//Only if it's current user
		$curUser = RequestContext::getMain()->getUser();
		if ( $curUser->getID() !== $user->getID() ) {
			return true;
		}

		wfProfileIn( __METHOD__ );

		//Find out all existing gadget preferences and save them in a map
		$preferencesCache = array();
		foreach ( $options as $option => $value ) {
			$m = array();
			if ( preg_match( '/gadget-([a-zA-Z](?:[-_:.\w\d ]*[a-zA-Z0-9])?)-config/', $option, $m ) ) {
				$gadgetName = $m[1];
				wfSuppressWarnings();				
				$gadgetPrefs = unserialize( $value );
				wfRestoreWarnings();
				if ( $gadgetPrefs !== false ) {
					$preferencesCache[$gadgetName] = $gadgetPrefs;
				} else {
					//should not happen; just in case
					wfDebug( __METHOD__ . ": couldn't unserialize settings for gadget " .
							"$gadgetName and user {$curUser->getID()}. Ignoring.\n" );
				}
				unset( $options[$option] );
			}
		}
		
		//Record preferences for each gadget
		$gadgets = Gadget::loadList();
		foreach ( $gadgets as $gadget ) {
			$prefsDescription = $gadget->getPrefsDescription();
			if ( $prefsDescription !== null ) {
				if ( isset( $preferencesCache[$gadget->getName()] ) ) {
					$userPrefs = $preferencesCache[$gadget->getName()];
				}
				
				if ( !isset( $userPrefs ) ) {
					$userPrefs = array(); //no saved prefs (or invalid entry in DB), use defaults
				}
				
				Gadget::matchPrefsWithDescription( $prefsDescription, $userPrefs );
				
				$gadget->setPrefs( $userPrefs );
			}
		}

		wfProfileOut( __METHOD__ );		
		return true;
	}

	/**
	 * UserSaveOptions hook handler.
	 * @param $user
	 * @param &$options 
	 */
	public static function userSaveOptions( $user, &$options ) {
		//Only if it's current user
		$curUser = RequestContext::getMain()->getUser();
		if ( $curUser->getID() !== $user->getID() ) {
			return true;
		}
		
		//Reinsert gadget-*-config options, so they can be saved back
		$gadgets = Gadget::loadList();
		
		if ( !$gadgets ) {
			return true;
		}
		
		foreach ( $gadgets as $gadget ) {
			if ( $gadget->getPrefs() !== null ) {
				//TODO: should remove prefs that equal their default

				$prefsSerialized = serialize( $gadget->getPrefs() );
				$options["gadget-{$gadget->getName()}-config"] = $prefsSerialized;
			}
		}
		
		return true;
	}

	/**
	 * Adds one legacy script to output.
	 * 
	 * @param $page String: Unprefixed page title
	 * @param $out OutputPage
	 */
	private static function applyScript( $page, $out ) {
		global $wgJsMimeType;

		# bug 22929: disable gadgets on sensitive pages.  Scripts loaded through the
		# ResourceLoader handle this in OutputPage::getModules()
		# TODO: make this extension load everything via RL, then we don't need to worry
		# about any of this.
		if( $out->getAllowedModules( ResourceLoaderModule::TYPE_SCRIPTS ) < ResourceLoaderModule::ORIGIN_USER_SITEWIDE ){
			return;
		}

		$t = Title::makeTitleSafe( NS_MEDIAWIKI, $page );
		if ( !$t ) return;

		$u = $t->getLocalURL( 'action=raw&ctype=' . $wgJsMimeType );
		$out->addScriptFile( $u, $t->getLatestRevID() );
	}

	/**
	 * UnitTestsList hook handler
	 * @param $files Array: List of extension test files
	 */
	public static function unitTestsList( $files ) {
		$files[] = dirname( dirname( __FILE__ ) ) . '/Gadgets_tests.php';
		return true;
	}
}
