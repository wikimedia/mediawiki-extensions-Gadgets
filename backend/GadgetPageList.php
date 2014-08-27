<?php
/**
 * Gadgets extension - lets users select custom javascript gadgets
 *
 *
 * For more info see http://mediawiki.org/wiki/Extension:Gadgets
 *
 * @file
 * @ingroup Extensions
 * @author Roan Kattouw
 * @copyright © 2011 Roan Kattouw
 * @license GNU General Public Licence 2.0 or later
 */

/**
 * Class with static methods for accessing the gadgetpagelist table
 */
class GadgetPageList {
	/**
	 * Determine the extension of a title ('css' or 'js')
	 * @param Title $title
	 * @return string The extension of the title, or empty string
	 */
	public static function determineExtension( Title $title ) {
		$m = null;
		preg_match( '!\.(css|js)$!u', $title->getText(), $m );
		return isset( $m[1] ) ? $m[1] : '';
	}

	/**
	 * Check whether a given title is a gadget page
	 * @param Title $title object
	 * @return bool True if $title is a CSS/JS page and isn't a redirect, false otherwise
	 */
	public static function isGadgetPage( Title $title ) {
		return ( $title->isCssOrJsPage() || $title->isCssJsSubpage() ) && !$title->isRedirect();
	}

	/**
	 * Get a row for the gadgetpagelist table
	 * @param Title $title
	 * @return array Database row
	 */
	public static function getRowForTitle( Title $title ) {
		return array(
			'gpl_extension' => self::determineExtension( $title ),
			'gpl_namespace' => $title->getNamespace(),
			'gpl_title' => $title->getDBKey()
		);
	}

	/**
	 * Update the status of a title, typically called when a title has been
	 * edited or created.
	 *
	 * If $title is a CSS/JS page and not a redirect, it is added to the table.
	 * If it is a CSS/JS page but is a redirect, it is removed from the table.
	 * If it's not a CSS/JS page, it's assumed never to have been added to begin with, so nothing happens/
	 * @param Title $title
	 */
	public static function updatePageStatus( Title $title ) {
		if ( $title->isCssOrJsPage() || $title->isCssJsSubpage() ) {
			if ( $title->isRedirect() ) {
				self::delete( $title );
			} else {
				self::add( $title );
			}
		}
	}

	/**
	 * Add a title to the gadgetpagelist table
	 * @param Title $title
	 */
	public static function add( Title $title ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert( 'gadgetpagelist', self::getRowForTitle( $title ),
			__METHOD__, array( 'IGNORE' )
		);
	}

	/**
	 * Delete a title from the gadgetpagelist table
	 * @param Title $title
	 */
	public static function delete( Title $title ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'gadgetpagelist', array(
				'gpl_namespace' => $title->getNamespace(),
				'gpl_title' => $title->getDBKey()
			), __METHOD__
		);
	}
}
