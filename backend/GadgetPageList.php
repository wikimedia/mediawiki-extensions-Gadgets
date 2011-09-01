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
	 * @param $title Title object
	 * @return string The extension of the title, or empty string
	 */
	public static function determineExtension( $title ) {
		$m = null;
		preg_match( '!\.(css|js)$!u', $title->getText(), $m );
		return isset( $m[1] ) ? $m[1] : '';
	}
	
	/**
	 * Add a title to the gadgetpagelist table
	 * @param $title Title object
	 */
	public static function add( $title ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert( 'gadgetpagelist', array(
				'gpl_extension' => self::determineExtension( $title ),
				'gpl_namespace' => $title->getNamespace(),
				'gpl_title' => $title->getPrefixedDBKey()
			), __METHOD__, array( 'IGNORE' )
		);
	}
	
	/**
	 * Delete a title from the gadgetpagelist table
	 * @param $title Title object
	 */
	public static function delete( $title ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'gadgetpagelist', array(
				'gpl_namespace' => $title->getNamespace(),
				'gpl_title' => $title->getPrefixedDBKey()
			), __METHOD__
		);
	}
}
