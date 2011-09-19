<?php
/**
 * SpecialPage for Gadget manager
 *
 * @file
 * @ingroup Extensions
 */

class SpecialGadgetManager extends SpecialPage {

	public function __construct() {
		parent::__construct( 'GadgetManager' );
	}

	/**
	 * @param $par String: Optionally the id of the gadget to show info for.
	 */
	public function execute( $par ) {
		$out = $this->getOutput();

		$this->setHeaders();
		$out->setPagetitle( wfMsg( 'gadgetmanager-title' ) );
		$out->addModuleStyles( 'ext.gadgets.gadgetmanager.prejs' );

		// Only load ajax editor if user is allowed to edit
		if ( $this->getUser()->isAllowed( 'gadgets-definition-edit' ) ) {
			$out->addModules( 'ext.gadgets.gadgetmanager.ui' );
		}

		// Determine view
		if ( is_string( $par ) && $par !== '' ) {
			$html = $this->generateGadgetView( $par );
		} else {
			$html = $this->generateOverview();
		}

		$out->addHtml( $html );
	}

	/**
	 * @return String: HTML
	 */
	private function generateOverview() {
		global $wgGadgetEnableSharing;

		$repo = LocalGadgetRepo::singleton();
		$gadgetsByCategory = $repo->getGadgetsByCategory();

		// If there there are no gadgets at all, exit early.
		if ( !count( $gadgetsByCategory ) ) {
			$noGadgetsMsgHtml = Html::element( 'p',
				array(
					'class' => 'mw-gadgetmanager-nogadgets'
				), wfMessage( 'gadgetmanager-nogadgets' )->plain()
			);
			$this->getOutput()->addHtml( $noGadgetsMsgHtml );
			return;
		}
		// There is atleast one gadget, let's get started.
		$this->getOutput()->addWikiMsg( 'gadgetmanager-pagetext', SpecialPage::getTitleFor( 'Recentchanges' )->getFullURL('namespace=' . NS_GADGET_DEFINITION ) );
		$html = '';
		
		// Sort categories alphabetically
		// @todo Sort causes the key "''" to be at the top, it should be on the bottom.
		ksort( $gadgetsByCategory );

		foreach ( $gadgetsByCategory as $category => $gadgets ) {
			// Avoid broken or empty headings. Fallback to a special message
			// for uncategorized gadgets (e.g. gadgets with category '' ).
			if ( $category !== '' ) {
				$categoryTitle = $repo->getCategoryTitle( $category );
			} else {
				$categoryTitle = wfMessage( 'gadgetmanager-uncategorized' )->plain();
			}

			// Category header
			$html .= Html::element( 'h2',
				array( 'class' => 'mw-gadgetmanager-category' ),
				$categoryTitle
			);

			// Start per-category gadgets table
			$html .= '<table class="mw-gadgetmanager-gadgets mw-datatable sortable"><thead><tr>';
			$html .=
				'<th>' . wfMessage( 'gadgetmanager-tablehead-title' )->escaped()
				. '</th><th>' . wfMessage( 'gadgetmanager-tablehead-default' )->escaped()
				. '</th><th>' . wfMessage( 'gadgetmanager-tablehead-hidden' )->escaped()
				. '</th>';
			if ( $wgGadgetEnableSharing ) {
				$html .= '<th>' . wfMessage( 'gadgetmanager-tablehead-shared' )->escaped() . '</th>';
			}
			$html .= '<th>' . wfMessage( 'gadgetmanager-tablehead-lastmod' )->escaped() . '</th>';
			$html .= '</tr></thead><tbody>';

			// Populate table rows for the current category
			foreach ( $gadgets as $gadgetId => $gadget ) {
				$html .= '<tr>';

				$tickedCheckboxHtml = Html::element( 'input', array(
					'type' => 'checkbox',
					'disabled' => 'disabled',
					'value' => 1,
					'checked' => 'checked',
				) );

				// Title
				$titleLink = Linker::link(
					$this->getTitle( $gadget->getId() ),
					$gadget->getTitleMessage(),
					array( 'data-gadget-id' => $gadget->getId() )
				);
				$html .= "<td class=\"mw-gadgetmanager-gadgets-title\">$titleLink</td>";
				// Default
				$html .= '<td class="mw-gadgetmanager-gadgets-default">'
					. ( $gadget->isEnabledByDefault() ? $tickedCheckboxHtml : '' ) . '</td>';
				// Hidden
				$html .= '<td class="mw-gadgetmanager-gadgets-hidden">'
					. ( $gadget->isHidden() ? $tickedCheckboxHtml : '' ) . '</td>';
				// Shared
				if ( $wgGadgetEnableSharing ) {
					$html .= '<td class="mw-gadgetmanager-gadgets-shared">'
						. ( $gadget->isShared() ? $tickedCheckboxHtml : '' ) . '</td>';
				}

				// Last modified
				$lastModText = '';
				$definitionTitle = Title::makeTitleSafe( NS_GADGET_DEFINITION, $gadget->getId() . '.js' );
				if ( $definitionTitle ) {
					$definitionRev = Revision::newFromTitle( $definitionTitle ); 
					if ( $definitionRev ) {
						$userLang = $this->getLang();
						$revTimestamp = $definitionRev->getTimestamp();
						$userText = $definitionRev->getUserText();
						$userLinks =
							Linker::userLink(
								$definitionRev->getUser(),
								$userText
							) .
							Linker::userToolLinks(
								$definitionRev->getUser(),
								$userText
							);
						$lastModText = wfMsgExt(
							'gadgetmanager-tablecell-lastmod',
							array( 'replaceafter', 'parseinline' ),
							array(
								$userLang->timeanddate( $revTimestamp, true ),
								$userLinks,
								$userLang->date( $revTimestamp, true ),
								$userLang->time( $revTimestamp, true ),
								$userText
							)
						);
					}
					$html .= "<td class=\"mw-gadgetmanager-gadgets-lastmod\">$lastModText</td>";
				}

				$html .= '</tr>';
			}

			// End of per-category gadgets table
			$html .= '</tbody></table>';
		}

		return $html;
	}

	/**
	 * @return String: HTML
	 */
	public function generateGadgetView( $gadgetId ) {
		return 'TODO - This page is about "'
			. htmlspecialchars( $gadgetId )
			. '". Also used as permalink from other places.';
	}
}
