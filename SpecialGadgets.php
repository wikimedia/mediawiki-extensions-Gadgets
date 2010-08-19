<?php
/**
 * Special:Gadgets, provides a preview of MediaWiki:Gadgets.
 *
 * @file
 * @ingroup SpecialPage
 * @author Daniel Kinzler, brightbyte.de
 * @copyright © 2007 Daniel Kinzler
 * @license GNU General Public License 2.0 or later
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
		parent::SpecialPage( 'Gadgets', '', true );
	}

	/**
	 * Main execution function
	 * @param $par Parameters passed to the page
	 */
	function execute( $par ) {
		global $wgRequest;
		
		$export = $wgRequest->getVal( 'export' );
		if ( $export ) {
			$this->showExportForm( $export );
		} else {
			$this->showMainForm();
		}
	}
	
	/**
	 * Displays form showing the list of installed gadgets
	 */
	public function showMainForm() {
		global $wgOut, $wgUser, $wgLang, $wgContLang;

		$skin = $wgUser->getSkin();

		$this->setHeaders();
		$wgOut->setPagetitle( wfMsg( "gadgets-title" ) );
		$wgOut->addWikiMsg( 'gadgets-pagetext' );

		$gadgets = wfLoadGadgetsStructured();
		if ( !$gadgets ) return;

		$lang = "";
		if ( $wgLang->getCode() != $wgContLang->getCode() ) {
			$lang = "/" . $wgLang->getCode();
		}

		$listOpen = false;

		$msgOpt = array( 'parseinline', 'parsemag' );
		$editInterfaceAllowed = $wgUser->isAllowed( 'editinterface' );
			
		foreach ( $gadgets as $section => $entries ) {
			if ( $section !== false && $section !== '' ) {
				$t = Title::makeTitleSafe( NS_MEDIAWIKI, "Gadget-section-$section$lang" );
				if ( $editInterfaceAllowed ) {
					$lnkTarget = $t
						? $skin->link( $t, wfMsgHTML( 'edit' ), array(), array( 'action' => 'edit' ) ) 
						: htmlspecialchars( $section );
					$lnk =  "&#160; &#160; [$lnkTarget]";
				} else {
					$lnk = '';
				}
				$ttext = wfMsgExt( "gadget-section-$section", $msgOpt );

				if( $listOpen ) {
					$wgOut->addHTML( Xml::closeElement( 'ul' ) . "\n" );
					$listOpen = false;
				}
				$wgOut->addHTML( Html::rawElement( 'h2', array(), $ttext . $lnk ) . "\n" );
			}

			foreach ( $entries as $gname => $code ) {
				$t = Title::makeTitleSafe( NS_MEDIAWIKI, "Gadget-$gname$lang" );
				if ( !$t ) continue;

				$links = array();
				if ( $editInterfaceAllowed ) {
					$links[] = $skin->link( $t, wfMsgHTML( 'edit' ), array(), array( 'action' => 'edit' ) );
				}
				$links[] = $skin->link( $this->getTitle(), wfMsgHtml( 'gadgets-export' ), 
					array(), array( 'export' => $gname )
				);
				
				$ttext = wfMsgExt( "gadget-$gname", $msgOpt );

				if( !$listOpen ) {
					$listOpen = true;
					$wgOut->addHTML( Xml::openElement( 'ul' ) );
				}
				$lnk = '&#160;&#160;' . wfMsg( 'parentheses', $wgLang->pipeList( $links ) );
				$wgOut->addHTML( Xml::openElement( 'li' ) .
						$ttext . $lnk . "<br />" .
						wfMsgHTML( 'gadgets-uses' ) . wfMsg( 'colon-separator' )
				);

				$lnk = array();
				foreach ( $code as $codePage ) {
					$t = Title::makeTitleSafe( NS_MEDIAWIKI, "Gadget-$codePage" );
					if ( !$t ) continue;

					$lnk[] = $skin->link( $t, htmlspecialchars( $t->getText() ) );
				}
				$wgOut->addHTML( $wgLang->commaList( $lnk ) );
				$wgOut->addHTML( Xml::closeElement( 'li' ) . "\n" );
			}
		}

		if( $listOpen ) {
			$wgOut->addHTML( Xml::closeElement( 'ul' ) . "\n" );
		}
	}

	/**
	 * Exports a gadget with its dependencies in a serialized form
	 * @param $gadget String Name of gadget to export
	 */
	public function showExportForm( $gadget ) {
		global $wgOut, $wgScript;

		$gadgets = wfLoadGadgets();
		if ( !isset( $gadgets[$gadget] ) ) {
			$wgOut->showErrorPage( 'error', 'gadgets-not-found', array( $gadget ) );
			return;
		}
		
		$ourDefinition = "* $gadget|" . implode('|', $gadgets[$gadget] );
		$this->setHeaders();
		$wgOut->setPagetitle( wfMsg( "gadgets-export-title" ) );
		$wgOut->addWikiMsg( 'gadgets-export-text', $gadget, $ourDefinition );

		$exportList = "MediaWiki:gadget-$gadget\n";
		foreach ( $gadgets[$gadget] as $page ) {
			$exportList .= "MediaWiki:gadget-$page\n";
		}

		$wgOut->addHTML( Html::openElement( 'form', array( 'method' => 'GET', 'action' => $wgScript ) )
			. Html::hidden( 'title', SpecialPage::getTitleFor( 'Export' )->getPrefixedDBKey() )
			. Html::hidden( 'pages', $exportList )
			. Html::hidden( 'wpDownload', '1' )
			. Xml::submitButton( wfMsg( 'gadgets-export-download' ) )
			. Html::closeElement( 'form' )
		);
	}
}
