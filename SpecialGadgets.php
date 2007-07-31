<?php
/**
 * Speclial:Gadgets, provides a preview of MediaWiki:Gadgets.
 * 
 * @addtogroup SpecialPage
 * @author Daniel Kinzler, brightbyte.de
 * @copyright © 2007 Daniel Kinzler
 * @licence GNU General Public Licence 2.0 or later
 */

if( !defined( 'MEDIAWIKI' ) ) {
	echo( "not a valid entry point.\n" );
	die( 1 );
}

/**
 *
 */
class SpecialGadgets extends SpecialPage {
	
	/**
	 * Constructor
	 */
	function __construct() {
		global $wgOut;
		SpecialPage::SpecialPage( 'Gadgets', '', true );

		#inject messages
		loadGadgetsI18n();
	}
	
	/**
	 * Main execution function
	 * @param $par Parameters passed to the page
	 */
	function execute( $par ) {
		global $wgOut, $wgUser;
		$skin =& $wgUser->getSkin();

		$wgOut->setPagetitle( wfMsg( "gadgets-title" ) );
		$wgOut->addWikiText( wfMsg( "gadgets-pagetext" ) );

		$gadgets = wfLoadGadgetsStructured();
		if ( !$gadgets ) return;

		$wgOut->addHTML( '<ul>' );

		$msgOpt = array( 'parseinline', 'parsemag' );

		foreach ( $gadgets as $section => $entries ) {
			if ( $section !== false && $section !== '' ) {
				$t = Title::makeTitleSafe( NS_MEDIAWIKI, $section );
				$lnk = $t ? $skin->makeLinkObj( $t, wfMsgHTML("edit") ) : htmlspecialchars($section);
				$ttext = wfMsgExt( $section, $msgOpt );

				$wgOut->addHTML( "\n<h2>$ttext &nbsp; &nbsp; [$lnk]</h2>\n" );
			}
	
			foreach ( $entries as $gname => $code ) {
				$t = Title::makeTitleSafe( NS_MEDIAWIKI, $gname );
				if ( !$t ) continue;

				$lnk = $skin->makeLinkObj( $t, wfMsgHTML("edit") );
				$ttext = wfMsgExt( $gname, $msgOpt );
		
				$wgOut->addHTML( "<li>" );
				$wgOut->addHTML( "$ttext &nbsp; &nbsp; [$lnk]<br/>" );

				$wgOut->addHTML( wfMsgHTML("gadgets-uses") . ": " );

				$first = true;
				foreach ( $code as $codePage ) {
					$t = Title::makeTitleSafe( NS_MEDIAWIKI, $codePage );
					if ( !$t ) continue;

					if ( $first ) $first = false;
					else $wgOut->addHTML(", ");

					$lnk = $skin->makeLinkObj( $t, htmlspecialchars( $t->getText() ) );
					$wgOut->addHTML($lnk);
				}

				$wgOut->addHtml( "</li>" );
			}
		}

		$wgOut->addHTML( '</ul>' );
	}
}
?>
