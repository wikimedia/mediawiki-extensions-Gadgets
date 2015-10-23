<?php
/**
 * Implements Special:GadgetUsage
 *
 * Copyright © 2015 Niharika Kohli
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup SpecialPage
 * @author Niharika Kohli <niharika@wikimedia.org>
 */

/**
 * Special:GadgetUsage - Lists all the gadgets on the wiki along with number of users.
 * @ingroup SpecialPage
 */
class SpecialGadgetUsage extends QueryPage {
	function __construct( $name = 'GadgetUsage' ) {
		parent::__construct( $name );
		$this->limit = 1000; // Show all gadgets
		$this->shownavigation = false;
	}

	public function isExpensive() {
		return true;
	}

	public function getQueryInfo() {
		$dbr = wfGetDB( DB_SLAVE );
		return array(
			'tables' => array( 'user_properties' ),
			'fields' => array(
				'title' => 'up_property',
				'value' => 'SUM( up_value )',
				'namespace' => NS_GADGET
			),
			'conds' => array(
				'up_property' . $dbr->buildLike( 'gadget-', $dbr->anyString() )
			),
			'options' => array(
				'GROUP BY' => array( 'up_property' )
			)
		);
	}

	public function getOrderFields() {
		return array( 'up_property' );
	}

	/**
	 * Output the start of the table
	 * Including opening <table>, and first <tr> with column headers.
	 */
	protected function outputTableStart() {
		$html = Html::openElement( 'table', array( 'class' => array( 'sortable', 'wikitable' ) ) );
		$html .= Html::openElement( 'tr', array() );

		$headers = array( 'gadgetusage-gadget', 'gadgetusage-usercount' );
		foreach( $headers as $h ) {
			$html .= Html::rawElement( 'th', array(), $this->msg( $h )->escaped() );
		}
		$html .= Html::closeElement( 'tr' );
		$this->getOutput()->addHTML( $html );
	}

	/**
	 * @param Skin $skin
	 * @param object $result Result row
	 * @return string bool
	 */
	public function formatResult( $skin, $result ) {
		$gadgetTitle = wfEscapeWikiText( substr( $result->title, 7 ) );
		$gadgetUserCount = $this->getLanguage()->formatNum( $result->value );
		if ( $gadgetTitle ) {
			$html = Html::openElement( 'tr', array() );
			$html .= Html::rawElement( 'td', array(), $gadgetTitle );
			$html .= Html::rawElement( 'td', array(), $gadgetUserCount );
			$html .= Html::closeElement( 'tr' );
			return $html;
		}
		return false;
	}

	/**
	 * Format and output report results using the given information plus
	 * OutputPage
	 *
	 * @param OutputPage $out OutputPage to print to
	 * @param Skin $skin User skin to use
	 * @param IDatabase $dbr Database (read) connection to use
	 * @param ResultWrapper $res Result pointer
	 * @param int $num Number of available result rows
	 * @param int $offset Paging offset
	 */
	protected function outputResults( $out, $skin, $dbr, $res, $num, $offset ) {
		if ( $num > 0 ) {
			$this->outputTableStart();

			foreach ( $res as $row ) {
				$line = $this->formatResult( $skin, $row );
				if ( $line ) {
					$out->addHTML( $line );
				}
			}
			// Close table element
			$out->addHtml( Html::closeElement( 'table' ) );
		} else {
			$out->addHtml(
				$this->msg( 'gadgetusage-noresults' )->parseAsBlock()
			);
		}
	}

	protected function getGroupName() {
		return 'wiki';
	}
}
