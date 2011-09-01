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
	 * @param $par String: Optionally the gadgetname to show info for.
	 */
	public function execute( $par ) {
		$out = $this->getOutput();

		$this->setHeaders();
		$out->setPagetitle( wfMsg( 'gadgetmanager-title' ) );
		$out->addModuleStyles( 'ext.gadgets.gadgetmanager.prejs' );

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

		$repo = new LocalGadgetRepo( array() );
		$gadgetNames = $repo->getGadgetNames();

		// If there there are no gadgets at all, exit early.
		if ( !count( $gadgetNames ) ) {
			$noGadgetsMsgHtml = Html::element( 'p',
				array(
					'class' => 'mw-gadgetmanager-nogadgets'
				), wfMessage( 'gadgetmanager-nogadgets' )->plain()
			);
			$this->getOutput()->addHtml( $noGadgetsMsgHtml );
			return;
		}
		// There is atleast one gadget, let's get started.
		$this->getOutput()->addWikiMsg( 'gadgetmanager-pagetext' );
		$html = '';

		// Sort gadgets by category
		$gadgetsByCategory = array();
		foreach ( $gadgetNames as $gadgetName ) {
			$gadget = $repo->getGadget( $gadgetName );
			$gadgetsByCategory[$gadget->getCategory()][$gadgetName] = $gadget;
		}

		// Sort categories alphabetically
		// @todo Sort causes the key "''" to be at the top, it should be on the bottom.
		ksort( $gadgetsByCategory );

		foreach ( $gadgetsByCategory as $category => $gadgets ) {
			// Avoid broken or empty headings. Fallback to a special message
			// for uncategorized gadgets (e.g. gadgets with category '' ).
			if ( $category !== '' ) {
				$categoryMsg = wfMessage( "gadgetcategory-$category" );
			} else {
				$categoryMsg = wfMessage( 'gadgetmanager-uncategorized' );
			}

			// Category header
			$html .= Html::element( 'h2',
				array( 'class' => 'mw-gadgetmanager-category' ),
				$categoryMsg->exists() ? $categoryMsg->plain() : $this->getLang()->ucfirst( $category )
			);

			// Start per-category gadgets table
			$html .= '<table class="mw-gadgetmanager-gadgets mw-datatable"><tr>';
			$html .=
				'<th>' . wfMessage( 'gadgetmanager-tablehead-title' )->escaped()
				. '</th><th>' . wfMessage( 'gadgetmanager-tablehead-default' )->escaped()
				. '</th><th>' . wfMessage( 'gadgetmanager-tablehead-hidden' )->escaped()
				. '</th>';
			if ( $wgGadgetEnableSharing ) {
				$html .= '<th>' . wfMessage( 'gadgetmanager-tablehead-shared' )->escaped() . '</th>';
			}
			$html .= '</tr>';

			// Populate table rows for the current category
			foreach ( $gadgets as $gadgetName => $gadget ) {
				$html .= '<tr>';

				$tickedCheckboxHtml = Html::element( 'input', array(
					'type' => 'checkbox',
					'disabled' => 'disabled',
					'value' => 1,
					'checked' => 'checked',
				) );

				// Title
				$titleMsg = wfMessage( $gadget->getTitleMsg() );
				$titleLink = Linker::link(
					$this->getTitle( $gadget->getName() ),
					// MediaWiki-message is optional. This is for backwards compatibility (since
					// the previous version didn't have titles), and to a allow wikis that only
					// care about one language to save from creating NS_MEDIAWIKI pages.
					// @todo: Centralize this logic.
					$titleMsg->exists() ? $titleMsg->plain() : $this->getLang()->ucfirst( $gadget->getName() )
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

				$html .= '</tr>';
			}

			// End of per-category gadgets table
			$html .= '</table>';
		}

		return $html;
	}

	/**
	 * @return String: HTML
	 */
	public function generateGadgetView( $gadgetName ) {
		return "Stub page where there will be some info about the gadget ($gadgetName). This is also used for permalinks to a gadget's config page.";
	}
}
