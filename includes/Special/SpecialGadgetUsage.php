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

use InvalidArgumentException;
use MediaWiki\Extension\Gadgets\GadgetRepo;
use MediaWiki\Html\Html;
use MediaWiki\Json\FormatJson;
use MediaWiki\MainConfigNames;
use MediaWiki\Output\OutputPage;
use MediaWiki\Skin\Skin;
use MediaWiki\SpecialPage\QueryPage;
use MediaWiki\Title\TitleValue;
use stdClass;
use Wikimedia\Rdbms\FakeResultWrapper;
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
	/**
	 * If a gadget is default, consider this to be the number of users for the table sort
	 * parameter. The number of opt-outs is deducted from this number so that after sorting,
	 * gadgets with least opt-outs are at the top.
	 */
	private const DEFAULT_SORT_VALUE = 10_000_000_000;

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
	 * SELECT up_property,
	 * 		COUNT(CASE WHEN up_value NOT IN ('0', '') THEN 1 END),
	 * 		COUNT(CASE WHEN up_value IN ('0','') THEN 1 END),
	 * 		COUNT(CASE WHEN up_value NOT IN ('0', '') AND qcc_title THEN 1 END),
	 * 		COUNT(CASE WHEN up_value IN ('0', '') AND qcc_title THEN 1 END)
	 * FROM user_properties
	 * LEFT JOIN user ON up_user = user_id
	 * LEFT JOIN querycachetwo ON user_name = qcc_title AND qcc_namespace = 2 AND qcc_type = 'activeusers'
	 * WHERE up_property LIKE 'gadget-%'
	 * GROUP BY up_property;
	 *
	 * @return array
	 */
	public function getQueryInfo() {
		$dbr = $this->dbProvider->getReplicaDatabase();

		$on = $dbr->expr( 'up_value', '!=', [ '0', '' ] );
		$off = $dbr->expr( 'up_value', '=', [ '0', '' ] );
		$activeOn = $on->and( 'qcc_title', '!=', null );
		$activeOff = $off->and( 'qcc_title', '!=', null );

		return [
			'tables' => [ 'user_properties', 'user', 'querycachetwo' ],
			'fields' => [
				'name' => 'up_property',
				'on' => 'COUNT(' . $dbr->conditional( $on, '1', 'NULL' ) . ')',
				'off' => 'COUNT(' . $dbr->conditional( $off, '1', 'NULL' ) . ')',
				'activeOn' => 'COUNT(' . $dbr->conditional( $activeOn, '1', 'NULL' ) . ')',
				'activeOff' => 'COUNT(' . $dbr->conditional( $activeOff, '1', 'NULL' ) . ')',
			],
			'conds' => [
				$dbr->expr( 'up_property', IExpression::LIKE, new LikeValue( 'gadget-', $dbr->anyString() ) )
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
		return [ $this->dbProvider->getReplicaDatabase()->addIdentifierQuotes( 'on' ) ];
	}

	/**
	 * Wrap the DB output in a FakeResultWrapper with a format that makes it cachable in querycache table.
	 * Note: changing the encoding format changes the API output for list=querypage&qppage=GadgetUsage.
	 * @inheritDoc
	 */
	public function reallyDoQuery( $limit, $offset = false ) {
		$res = parent::reallyDoQuery( $limit, $offset );
		if ( $this->usesExternalSource() ) {
			return $res;
		}

		$rows = [];
		foreach ( $res as $row ) {
			$title = FormatJson::encode( [
				'name' => $row->name,
				'on' => (int)$row->on,
				'off' => (int)$row->off,
				'activeOn' => (int)$row->activeOn,
				'activeOff' => (int)$row->activeOff,
			] );
			$rows[] = (object)[
				// Preserve underscores when ApiQueryQueryPage normalizes titles to use spaces.
				'title' => str_replace( '_', '\\u005f', $title ),
				'value' => $row->on,
				// Avoid a namespace prefix in the API so the title remains valid JSON.
				'namespace' => NS_MAIN,
			];
		}

		return new FakeResultWrapper( $rows );
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
		$data = $this->decodeFakeTitle( $result->title );
		if ( !$data ) {
			return false;
		}
		$gadgetTitle = substr( $data->name, 7 );
		$isDefault = $this->isGadgetDefault( $gadgetTitle );
		if ( $isDefault === null ) {
			// Gadget doesn't exist any more
			return false;
		}
		if ( $gadgetTitle ) {
			$html = '';

			// "Gadget" column
			$link = $this->getLinkRenderer()->makeLink(
				new TitleValue( NS_SPECIAL, 'Gadgets', 'gadget-' . $gadgetTitle ),
				$gadgetTitle
			);
			$html .= Html::rawElement( 'td', [], $link );

			// "Number of users" column
			if ( $isDefault ) {
				$attrs = [ 'data-sort-value' => self::DEFAULT_SORT_VALUE - $data->off ];
				$html .= Html::element( 'td', $attrs,
					$this->msg( 'gadgetusage-default-optouts' )->numParams( $data->off )->text() );
			} else {
				$html .= Html::element( 'td', [], $this->getLanguage()->formatNum( $data->on ) );
			}

			// "Active users" column
			if ( $isDefault ) {
				$attrs = [ 'data-sort-value' => self::DEFAULT_SORT_VALUE - $data->activeOff ];
				$html .= Html::element( 'td', $attrs,
					$this->msg( 'gadgetusage-default-optouts' )->numParams( $data->activeOff )->text() );
			} else {
				$activeUserCount = $this->getLanguage()->formatNum( $data->activeOn );
				$html .= Html::element( 'td', [], $activeUserCount );
			}
			return Html::rawElement( 'tr', [], $html );
		}
		// TODO: also display gadgets with 0 users (which don't show up because they're not in user_properties table),
		// as well as default gadgets with 0 opt-outs.
		return false;
	}

	private function decodeFakeTitle( string $str ): ?stdClass {
		if ( !str_starts_with( $str, '{' ) || !str_ends_with( $str, '}' ) ) {
			// Unexpected format, or truncated title due to gadget name being too long
			return null;
		}
		$data = FormatJson::decode( $str );
		if ( !$data || !isset( $data->name ) || !isset( $data->on ) || !isset( $data->off ) ||
			!isset( $data->activeOn ) || !isset( $data->activeOff ) ) {
			return null;
		}
		return $data;
	}

	/**
	 * @param string $gadgetName
	 * @return bool|null Whether the gadget is default. Null if gadget doesn't exist.
	 */
	private function isGadgetDefault( string $gadgetName ): ?bool {
		try {
			$gadget = $this->gadgetRepo->getGadget( $gadgetName );
			return $gadget->isOnByDefault();
		} catch ( InvalidArgumentException ) {
			return null;
		}
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
		$out->addHtml(
			$this->msg( 'gadgetusage-intro' )
				->numParams( $this->getConfig()->get( MainConfigNames::ActiveUserDays ) )->parseAsBlock()
		);
		if ( $num > 0 ) {
			$this->outputTableStart();

			foreach ( $res as $row ) {
				$line = $this->formatResult( $skin, $row );
				if ( $line ) {
					$out->addHTML( $line );
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
