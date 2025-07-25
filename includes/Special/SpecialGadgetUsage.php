<?php
/**
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
 */

namespace MediaWiki\Extension\Gadgets\Special;

use MediaWiki\Extension\Gadgets\GadgetRepo;
use MediaWiki\Html\Html;
use MediaWiki\Output\OutputPage;
use MediaWiki\Skin\Skin;
use MediaWiki\SpecialPage\QueryPage;
use MediaWiki\Title\TitleValue;
use stdClass;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\IReadableDatabase;
use Wikimedia\Rdbms\IResultWrapper;
use Wikimedia\Rdbms\LikeValue;

/**
 * Special:GadgetUsage lists all the gadgets on the wiki along with number of users.
 *
 * @copyright 2015 Niharika Kohli
 */
class SpecialGadgetUsage extends QueryPage {
	public function __construct(
		private readonly GadgetRepo $gadgetRepo,
		private readonly IConnectionProvider $dbProvider,
	) {
		parent::__construct( 'GadgetUsage' );
		// Show all gadgets
		$this->limit = 1000;
		$this->shownavigation = false;
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $par ) {
		parent::execute( $par );
		$this->addHelpLink( 'Extension:Gadgets' );
	}

	/** @inheritDoc */
	public function isExpensive() {
		return true;
	}

	/**
	 * Define the database query that is used to generate the stats table.
	 * The query is essentially:
	 *
	 * SELECT up_property, COUNT(*), count(qcc_title)
	 * FROM user_properties
	 * LEFT JOIN user ON up_user = user_id
	 * LEFT JOIN querycachetwo ON user_name = qcc_title AND qcc_namespace = 2 AND qcc_type = 'activeusers'
	 * WHERE up_property LIKE 'gadget-%' AND up_value NOT IN ('0','')
	 * GROUP BY up_property;
	 *
	 * @return array
	 */
	public function getQueryInfo() {
		$dbr = $this->dbProvider->getReplicaDatabase();

		return [
			'tables' => [ 'user_properties', 'user', 'querycachetwo' ],
			'fields' => [
				'title' => 'up_property',
				'value' => 'COUNT(*)',
				// Need to pick fields existing in the querycache table so that the results are cachable
				'namespace' => 'COUNT( qcc_title )'
			],
			'conds' => [
				$dbr->expr( 'up_property', IExpression::LIKE, new LikeValue( 'gadget-', $dbr->anyString() ) ),
				// Simulate php falsy condition to ignore disabled user preferences
				$dbr->expr( 'up_value', '!=', [ '0', '' ] ),
			],
			'options' => [
				'GROUP BY' => [ 'up_property' ]
			],
			'join_conds' => [
				'user' => [
					'LEFT JOIN', [
						'up_user = user_id'
					]
				],
				'querycachetwo' => [
					'LEFT JOIN', [
						'user_name = qcc_title',
						'qcc_namespace' => NS_USER,
						'qcc_type' => 'activeusers',
					]
				]
			]
		];
	}

	/** @inheritDoc */
	public function getOrderFields() {
		return [ 'value' ];
	}

	/**
	 * Output the start of the table
	 * Including opening <table>, the thead element with column headers
	 * and the opening <tbody>.
	 */
	protected function outputTableStart() {
		$html = '';
		$headers = [ 'gadgetusage-gadget', 'gadgetusage-usercount', 'gadgetusage-activeusers' ];
		foreach ( $headers as $h ) {
			if ( $h === 'gadgetusage-gadget' ) {
				$html .= Html::element( 'th', [], $this->msg( $h )->text() );
			} else {
				$html .= Html::element( 'th', [ 'data-sort-type' => 'number' ],
					$this->msg( $h )->text() );
			}
		}

		$this->getOutput()->addHTML(
			Html::openElement( 'table', [ 'class' => [ 'sortable', 'wikitable' ] ] ) .
			Html::rawElement( 'thead', [], Html::rawElement( 'tr', [], $html ) ) .
			Html::openElement( 'tbody', [] )
		);
		$this->getOutput()->addModuleStyles( 'jquery.tablesorter.styles' );
		$this->getOutput()->addModules( 'jquery.tablesorter' );
	}

	/**
	 * Output the end of the table
	 * </tbody></table>
	 */
	protected function outputTableEnd() {
		$this->getOutput()->addHTML(
			Html::closeElement( 'tbody' ) .
			Html::closeElement( 'table' )
		);
	}

	/**
	 * @param Skin $skin
	 * @param stdClass $result Result row
	 * @return string|bool String of HTML
	 */
	public function formatResult( $skin, $result ) {
		$gadgetTitle = substr( $result->title, 7 );
		$gadgetUserCount = $this->getLanguage()->formatNum( $result->value );
		if ( $gadgetTitle ) {
			$html = '';
			// "Gadget" column
			$link = $this->getLinkRenderer()->makeLink(
				new TitleValue( NS_SPECIAL, 'Gadgets', 'gadget-' . $gadgetTitle ),
				$gadgetTitle
			);
			$html .= Html::rawElement( 'td', [], $link );
			// "Number of users" column
			$html .= Html::element( 'td', [], $gadgetUserCount );
			// "Active users" column
			$activeUserCount = $this->getLanguage()->formatNum( $result->namespace );
			$html .= Html::element( 'td', [], $activeUserCount );
			return Html::rawElement( 'tr', [], $html );
		}
		return false;
	}

	/**
	 * Get a list of default gadgets
	 * @param array $gadgetIds list of gagdet ids registered in the wiki
	 * @return array
	 */
	protected function getDefaultGadgets( $gadgetIds ) {
		$gadgetsList = [];
		foreach ( $gadgetIds as $g ) {
			$gadget = $this->gadgetRepo->getGadget( $g );
			if ( $gadget->isOnByDefault() ) {
				$gadgetsList[] = $gadget->getName();
			}
		}
		asort( $gadgetsList, SORT_STRING | SORT_FLAG_CASE );
		return $gadgetsList;
	}

	/**
	 * Format and output report results using the given information plus
	 * OutputPage
	 *
	 * @param OutputPage $out OutputPage to print to
	 * @param Skin $skin User skin to use
	 * @param IReadableDatabase $dbr Database (read) connection to use
	 * @param IResultWrapper $res Result pointer
	 * @param int $num Number of available result rows
	 * @param int $offset Paging offset
	 */
	protected function outputResults( $out, $skin, $dbr, $res, $num, $offset ) {
		$gadgetIds = $this->gadgetRepo->getGadgetIds();
		$defaultGadgets = $this->getDefaultGadgets( $gadgetIds );
		$out->addHtml(
			$this->msg( 'gadgetusage-intro' )
				->numParams( $this->getConfig()->get( 'ActiveUserDays' ) )->parseAsBlock()
		);
		if ( $num > 0 ) {
			$this->outputTableStart();
			// Append default gadgets to the table with 'default' in the total and active user fields
			foreach ( $defaultGadgets as $default ) {
				$html = '';
				// "Gadget" column
				$link = $this->getLinkRenderer()->makeLink(
					new TitleValue( NS_SPECIAL, 'Gadgets', 'gadget-' . $default ),
					$default
				);
				$html .= Html::rawElement( 'td', [], $link );
				// "Number of users" column
				$html .= Html::element( 'td', [ 'data-sort-value' => 'Infinity' ],
					$this->msg( 'gadgetusage-default' )->text() );
				// "Active users" column
				// @phan-suppress-next-line PhanPluginDuplicateAdjacentStatement
				$html .= Html::element( 'td', [ 'data-sort-value' => 'Infinity' ],
					$this->msg( 'gadgetusage-default' )->text() );
				$out->addHTML( Html::rawElement( 'tr', [], $html ) );
			}
			foreach ( $res as $row ) {
				// Remove the 'gadget-' part of the result string and compare if it's present
				// in $defaultGadgets, if not we format it and add it to the output
				$name = substr( $row->title, 7 );

				// Only pick gadgets which are in the list $gadgetIds to make sure they exist
				if ( !in_array( $name, $defaultGadgets, true ) && in_array( $name, $gadgetIds, true ) ) {
					$line = $this->formatResult( $skin, $row );
					if ( $line ) {
						$out->addHTML( $line );
					}
				}
			}
			// Close table element
			$this->outputTableEnd();
		} else {
			$out->addHtml(
				$this->msg( 'gadgetusage-noresults' )->parseAsBlock()
			);
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function getGroupName() {
		return 'wiki';
	}
}
