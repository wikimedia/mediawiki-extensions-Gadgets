<?php
/**
 * SpecialPage for Gadgets.
 *
 * @file
 * @ingroup Extensions
 */

class SpecialGadgets extends SpecialPage {

	/**
	 * @var $params Array: Parameters passed to the page.
	 * - gadget String|null: Gadget id
	 * - action String: Action ('view', 'export')
	 */
	protected $params = array(
		'gadget' => null,
		'action' => 'view',
	);

	public function __construct() {
		parent::__construct( 'Gadgets' );
	}

	/**
	 * Main execution function.
	 * @todo: Add canonical links to <head> to avoid indexing of link variations and stuff like
	 * [[Special:Gadgets/id/export/bablabla]]. Those should either redirect and/or have a canonical
	 * link in the <head> ($out->addLink).
	 * @param $par String: Parameters passed to the page.
	 */
	public function execute( $par ) {
		$this->par = $par;
		$out = $this->getOutput();
		$out->addModuleStyles( 'ext.gadgets.specialgadgets.prejs' );
		$out->addModules( 'ext.gadgets.specialgadgets.tabs' );

		// Map title parts to query string
		if ( is_string( $par ) ) {
			$parts = explode( '/', $par, 3 );
			$this->params['gadget'] = $parts[0];
			if ( isset( $parts[1] ) ) {
				$this->params['action'] = $parts[1];
			}
		}

		// Parameters (overrides title parts)
		$this->params['gadget'] = $this->getRequest()->getVal( 'gadget', $this->params['gadget'] );
		$this->params['action'] = $this->getRequest()->getVal( 'action', $this->params['action'] );

		// Get instance of Gadget
		$gadget = false;
		if ( !is_null( $this->params['gadget'] ) ) {
			$repo = LocalGadgetRepo::singleton();
			$gadget = $repo->getGadget( $this->params['gadget'] );
			if ( !is_object( $gadget ) ) {
				$out->showErrorPage( 'error', 'gadgets-not-found', array( $this->params['gadget'] ) );
				return;
			}
		}

		// Handle the the query
		switch( $this->params['action'] ) {
			case 'view':
				if ( $gadget ) {
					$this->showSingleGadget( $gadget );
				} else {
					$this->showAllGadgets();
				}
				break;
			case 'export':
				if ( $gadget ) {
					$this->showExportForm( $gadget );
				} else {
					$out->showErrorPage( 'error', 'gadgets-nosuchaction' );
				}
				break;
			default:
				$out->showErrorPage( 'error', 'gadgets-nosuchaction' );
				break;
		}
	}

	/**
	 * Returns one <div class="mw-gadgets-gadget">..</div>
	 * for the given Gadget object.
	 */
	protected function getGadgetHtml( Gadget $gadget ) {
		global $wgContLang;
		$user = $this->getUser();
		$userlang = $this->getLang();

		// Suffix needed after page names in links to NS_MEDIAWIKI,
		// e.g. to link to [[MediaWiki:Foo/nl]] instead of [[MediaWiki:Foo]]
		$suffix = '';
		if ( $userlang->getCode() !== $wgContLang->getCode() ) {
			$suffix = '/' . $userlang->getCode();
		}

		$html = Html::openElement( 'div', array(
			'class' => 'mw-gadgets-gadget',
			'data-gadget-id' => $gadget->getId(),
		) );

		// Gadgetlinks section in the Gadget title heading
		$extra = array();

		$extra[] = Linker::link(
			$this->getTitle( $gadget->getId() ),
			wfMessage( 'gadgets-gadget-permalink' )->escaped(),
			array(
				'title' => wfMessage( 'gadgets-gadget-permalink-tooltip', $gadget->getId() )->plain(),
				'class' => 'mw-gadgets-permalink',
			)
		);

		$extra[] = Linker::link(
			$this->getTitle( "{$gadget->getId()}/export" ),
			wfMessage( 'gadgets-gadget-export' )->escaped(),
			array(
				'title' => wfMessage( 'gadgets-gadget-export-tooltip', $gadget->getId() )->plain(),
				'class' => 'mw-gadgets-export',
			)

		);
		$gadgetDefinitionTitle = GadgetsHooks::getDefinitionTitleFromID( $gadget->getId() );

		if ( $gadgetDefinitionTitle instanceof Title ) {

			if ( $user->isAllowed( 'gadgets-definition-edit' ) ) {
				$extra[] = Linker::link(
					$gadgetDefinitionTitle,
					wfMessage( 'gadgets-gadget-modify' )->escaped(),
					array(
						'title' => wfMessage( 'gadgets-gadget-modify-tooltip', $gadget->getId() )->plain(),
						'class' => 'mw-gadgets-modify',
					),
					array( 'action' => 'edit' )
				);
			}

			if ( $user->isAllowed( 'gadgets-definition-delete' ) ) {
				$extra[] = Linker::link(
					$gadgetDefinitionTitle,
					wfMessage( 'gadgets-gadget-delete' )->escaped(),
					array(
						'title' => wfMessage( 'gadgets-gadget-delete-tooltip', $gadget->getId() )->plain(),
						'class' => 'mw-gadgets-delete',
					),
					array( 'action' => 'delete' )
				);
			}
		}

		// Edit interface (gadget title and description)
		$editTitle = $editDescription = '';
		if ( $user->isAllowed( 'editinterface' ) ) {
			$t = Title::makeTitleSafe( NS_MEDIAWIKI, $gadget->getTitleMessageKey() . $suffix );
			if ( $t ) {
				$editLink = Linker::link(
					$t,
					wfMessage( 'gadgets-message-edit' )->escaped(),
					array( 'title' => wfMessage( 'gadgets-message-edit-tooltip', $t->getPrefixedText() )->plain() ),
					array( 'action' => 'edit' )
				);
				$editTitle = '<span class="mw-gadgets-messagelink">' . $editLink . '</span>';
			}

			$t = Title::makeTitleSafe( NS_MEDIAWIKI, $gadget->getDescriptionMessageKey() . $suffix );
			if ( $t ) {
				$editLink = Linker::link(
					$t,
					wfMessage( $t->isKnown() ? 'gadgets-desc-edit' : 'gadgets-desc-add' )->escaped(),
					array( 'title' => wfMessage( $t->isKnown() ? 'gadgets-desc-edit-tooltip' : 'gadgets-desc-add-tooltip', $t->getPrefixedText() )->plain() ),
					array( 'action' => 'edit' )
				);
				$editDescription = '<span class="mw-gadgets-messagelink">' . $editLink . '</span>';
			}
		}

		// Gadget heading
		$html .= '<div class="mw-gadgets-title">'
			. htmlspecialchars( $gadget->getTitleMessage() )
			. ' &#160; ' . $editTitle
			. Html::rawElement( 'span', array(
					'class' => 'mw-gadgets-gadgetlinks',
					'data-gadget-id' => $gadget->getId()
				), implode( '', $extra )
			)
			. '</div>';

		// Description
		$html .= Html::rawElement( 'p', array(
			'class' => 'mw-gadgets-description'
		), $gadget->getDescriptionMessage() . '&#160;' . $editDescription );

		$html .= '</div>';
		return $html;
	}

	/**
	 * Handles [[Special:Gadgets]].
	 * Displays form showing the list of installed gadgets.
	 */
	public function showAllGadgets() {
		global $wgContLang;
		$out = $this->getOutput();
		$user = $this->getUser();
		$userlang = $this->getLang();

		$this->setHeaders();
		$out->setPagetitle( wfMsg( 'gadgets-title' ) );

		$repo = LocalGadgetRepo::singleton();
		$gadgetsByCategory = $repo->getGadgetsByCategory();

		// Only load the gadget manager module if needed
		if ( $user->isAllowed( 'gadgets-definition-delete' )
			|| $user->isAllowed( 'gadgets-definition-edit' )
			|| $user->isAllowed( 'gadgets-definition-create' )
		) {
			$out->addModules( 'ext.gadgets.gadgetmanager' );
		}

		// If there there are no gadgets at all, exit early.
		if ( !count( $gadgetsByCategory ) ) {
			$noGadgetsMsgHtml = Html::element( 'p',
				array(
					'class' => 'mw-gadgets-nogadgets'
				), wfMessage( 'gadgets-nogadgets' )->plain()
			);
			$this->getOutput()->addHtml( $noGadgetsMsgHtml );
			return;
		}

		// There is atleast one gadget, let's get started.
		$out->addWikiMsg( 'gadgets-pagetext',
			SpecialPage::getTitleFor( 'Recentchanges', 'namespace=' . NS_GADGET_DEFINITION )->getPrefixedText()
		);

		// Sort categories alphabetically
		ksort( $gadgetsByCategory );

		// ksort causes key "''" to be sorted on top, we want it to be at the bottom,
		// removing and re-adding the value.
		if ( isset( $gadgetsByCategory[''] ) ) {
			$uncat = $gadgetsByCategory[''];
			unset( $gadgetsByCategory[''] );
			$gadgetsByCategory[''] = $uncat;
		}

		// Suffix needed after page names in links to NS_MEDIAWIKI,
		// e.g. to link to [[MediaWiki:Foo/nl]] instead of [[MediaWiki:Foo]]
		$suffix = '';
		if ( $userlang->getCode() !== $wgContLang->getCode() ) {
			$suffix = '/' . $userlang->getCode();
		}


		$html = '';

		foreach ( $gadgetsByCategory as $category => $gadgets ) {

			// Avoid broken or empty headings. Fallback to a special message
			// for uncategorized gadgets (e.g. gadgets with category '' ).
			if ( $category !== '' ) {
				$categoryTitle = $repo->getCategoryTitle( $category );
			} else {
				$categoryTitle = wfMessage( 'gadgets-uncategorized' )->plain();
			}

			$editLink = '';
			if ( $user->isAllowed( 'editinterface' ) && $category !== '' ) {
				$t = Title::makeTitleSafe( NS_MEDIAWIKI, "gadgetcategory-{$category}{$suffix}" );
				if ( $t ) {
					$editLink = Linker::link(
						$t,
						wfMessage( 'gadgets-message-edit' )->escaped(),
						array( 'title' => wfMessage( 'gadgets-message-edit-tooltip', $t->getPrefixedText() ) ),
						array( 'action' => 'edit' )
					);
					$editLink = '<span class="mw-gadgets-messagelink">' . $editLink . '</span>';
				}
			}

			// Category heading
			$html .= Html::rawElement( 'h2', array(), htmlspecialchars( $categoryTitle ) . ' &#160; ' . $editLink );

			// Start gadgets list
			$html .= '<div class="mw-gadgets-list">';

			foreach( $gadgets as $gadgetId => $gadget ) {
				$html .= $this->getGadgetHtml( $gadget );

			}

			$html .= '</div>';
		}

		$out->addHtml( $html );
	}

	/**
	 * Handles [[Special:Gadgets/id/export]].
	 * Exports a gadget with its dependencies in a serialized form.
	 * Should not be called if the gadget does not exist. $gadget must be
	 * an instance of Gadget, not null.
	 * @param $gadget Gadget: Gadget object of gadget to export.
	 */
	public function showExportForm( $gadget ) {
		$this->doSubpageMode();
		$out = $this->getOutput();

		/**
		 * @todo: Add note somewhere with link to mw.org help pages about gadget repos
		 * if this is a shared gadget and the user owns the wiki, he is recommended
		 * to instead pull from this repo natively.
		 */

		$rights = array(
			'gadgets-definition-create',
			'gadgets-definition-edit',
			'gadgets-edit',
			'importupload',
		);
		$msg = array();
		foreach( $rights as $right ) {
			$msg[] = Html::element( 'code', array(
					'style' => 'white-space:nowrap',
					'title' => wfMsg( "right-{$right}" )
				), $right
			);
		}

		$this->setHeaders();
		$out->setPagetitle( wfMsg( 'gadgets-export-title', $gadget->getTitleMessage() ) );

		// Make a list of all pagenames to be exported:
		$exportTitles = array();

		// NS_GADGET_DEFINITION page of this gadget
		$exportTitles[] = GadgetsHooks::getDefinitionTitleFromID( $gadget->getId() );

		// Title message in NS_MEDIAWIKI
		$exportTitles[] = Title::makeTitleSafe( NS_MEDIAWIKI, $gadget->getTitleMessageKey() );

		// Translation subpages of title message
		// @todo

		// Description message in NS_MEDIAWIKI
		$exportTitles[] = Title::makeTitleSafe( NS_MEDIAWIKI, $gadget->getDescriptionMessageKey() );

		// Translation subpages of description message
		// @todo

		// Module script and styles in NS_GADGET
		foreach ( $gadget->getScripts() as $script ) {
			$exportTitles[] = Title::makeTitleSafe( NS_GADGET, $script );
		}

		foreach ( $gadget->getStyles() as $style ) {
			$exportTitles[] = Title::makeTitleSafe( NS_GADGET, $style );
		}

		$gadgetModule = $gadget->getModule();

		// Module messages in NS_MEDIAWIKI
		foreach( $gadgetModule->getMessages() as $message ) {
			$exportTitles[] = Title::makeTitleSafe( NS_MEDIAWIKI, $message );
		}

		// Translation subpages of module messages
		// @todo

		// Build line-break separated string of prefixed titles
		$exportList = '';
		// Build html for unordered list with links to the titles
		$exportDisplayList = '<ul>';
		foreach ( $exportTitles as $exportTitle ) {
			// Make sure it's not null (for inexisting or invalid title)
			// and addionally check exists() to avoid exporting messages
			// from NS_MEDIAWIKI that don't exist but are 'isAlwaysKnown'
			// due to their default value from PHP messages files
			// (which we don't want to export)
			if ( $exportTitle && $exportTitle->exists() ) {
				$exportList .= $exportTitle->getPrefixedDBkey() . "\n";
				$exportDisplayList .= '<li>'. Linker::link( $exportTitle ) . '</li>';
			}
		}
		$exportDisplayList .= '</ul>';

		global $wgScript;
		$form =
			Html::openElement( 'form', array(
				'method' => 'get',
				'action' => $wgScript,
				'class' => 'mw-gadgets-exportform'
			) )
			. '<fieldset><p>'
			. wfMessage( 'gadgets-export-text' )
				->rawParams(
					htmlspecialchars( $gadget->getId() ),
					'', // $2 is no longer used. To avoid breaking backwards compatibility, skipped here and
					// $3 is used for the new message part
					$this->getLang()->listToText( $msg )
				)
				->escaped()
			. '</p>'
			. $exportDisplayList
			. Html::hidden( 'title', SpecialPage::getTitleFor( 'Export' )->getPrefixedDBKey() )
			. Html::hidden( 'pages', $exportList )
			. Html::hidden( 'wpDownload', '1' )
			. Html::hidden( 'templates', '1' )
			. Xml::submitButton( wfMsg( 'gadgets-export-download' ) )
			. '</fieldset></form>';

		$out->addHTML( $form );
	}

	/**
	 * Handles [[Special:Gadgets/id]].
	 * Should not be called if the gadget does not exist. $gadget must be
	 * an instance of Gadget, not null.
	 * @param $gadget Gadget
	 */
	public function showSingleGadget( Gadget $gadget ) {
		$this->doSubpageMode();
		$out = $this->getOutput();
		$user = $this->getUser();

		$this->setHeaders();
		$out->setPagetitle( wfMsg( 'gadgets-gadget-title', $gadget->getTitleMessage() ) );

		// Only load the gadget manager module if needed
		if ( $user->isAllowed( 'gadgets-definition-delete' )
			|| $user->isAllowed( 'gadgets-definition-edit' )
			|| $user->isAllowed( 'gadgets-definition-create' )
		) {
			$out->addModules( 'ext.gadgets.gadgetmanager' );
		}

		$out->addHTML( '<div class="mw-gadgets-list">' . $this->getGadgetHtml( $gadget ) . '</div>' );
	}


	/**
	 * Call this method internally to include a breadcrumb navigation on top of the page.
	 * Cannot be undone, should only be called once.
	 * @return Boolean: True if added, false if not added because already added.
	 */
	public function doSubpageMode() {
		static $done = false;
		if ( $done ) {
			return false;
		}
		$done = true;

		// Would be nice if we wouldn't have to duplicate
		// this from Skin::subPageSubtitle. Slightly modified though
		$subpages = '';
		$ptext = $this->getTitle( $this->par )->getPrefixedText();
		if ( preg_match( '/\//', $ptext ) ) {
			$links = explode( '/', $ptext );
			array_pop( $links );
			$growinglink = '';
			$display = '';
			$c = 0;

			foreach ( $links as $link ) {
				$growinglink .= $link;
				$display .= $link;
				$linkObj = Title::newFromText( $growinglink );

				if ( is_object( $linkObj ) ) {
					$getlink = Linker::link( $linkObj, htmlspecialchars( $display ) );

					$c++;
					if ( $c > 1 ) {
						$subpages .= wfMessage( 'pipe-separator' )->escaped();
					} else {
						// First iteration
						$subpages .= '&lt; ';
					}

					$subpages .= $getlink;
					$display = '';
				} else {
					$display .= '/';
				}
				$growinglink .= '/';
			}
		}
		$this->getOutput()->setSubtitle( $subpages );
		return true;
	}
}
