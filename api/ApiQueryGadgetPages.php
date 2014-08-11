<?php
/**
 * Created on 15 April 2011
 * API for Gadgets extension
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
 */

class ApiQueryGadgetPages extends ApiQueryGeneratorBase {
	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'gp' );
	}

	public function getCacheMode( $params ) {
		return 'public';
	}

	public function execute() {
		$this->run();
	}

	/**
	 * @param $resultPageSet ApiPageSet
	 * @return void
	 */
	public function executeGenerator( $resultPageSet ) {
		$this->run( $resultPageSet );
	}

	/**
	 * @param $resultPageSet ApiPageSet
	 * @return void
	 */
	private function run( $resultPageSet = null ) {
		$params = $this->extractRequestParams();
		$db = $this->getDB();
		
		$this->addTables( 'gadgetpagelist' );
		$this->addFields( array( 'gpl_namespace', 'gpl_title' ) );
		$this->addWhereFld( 'gpl_extension', $params['extension'] );
		$this->addWhereFld( 'gpl_namespace', $params['namespace'] );
		if ( $params['prefix'] !== null ) {
			$this->addWhere( 'gpl_title' . $db->buildLike( $this->titlePartToKey( $params['prefix'] ), $db->anyString() ) );
		}
		$limit = $params['limit'];
		$this->addOption( 'LIMIT', $limit + 1 );
		$dir = ( $params['dir'] == 'descending' ? 'older' : 'newer' );
		$from = $params['from'] !== null ? $this->titlePartToKey( $params['from'] ) : null;
		$this->addWhereRange( 'gpl_title', $dir, $from, null );
		
		$res = $this->select( __METHOD__ );

		$count = 0;
		$titles = array();
		$result = $this->getResult();
		foreach ( $res as $row ) {
			if ( ++$count > $limit ) {
				// We've reached the one extra which shows that there are additional pages to be had. Stop here...
				$this->setContinueEnumParameter( 'from', $this->keyToTitle( $row->gpl_title ) );
				break;
			}
			
			$title = Title::makeTitle( $row->gpl_namespace, $row->gpl_title );
			if ( is_null( $resultPageSet ) ) {
				$vals = array( 'pagename' => $title->getText() );
				self::addTitleInfo( $vals, $title );
				$fit = $result->addValue( array( 'query', $this->getModuleName() ), null, $vals );
				if ( !$fit ) {
					$this->setContinueEnumParameter( 'from', $this->keyToTitle( $row->gpl_title ) );
					break;
				}
			} else {
				$titles[] = $title;
			}
		}

		if ( is_null( $resultPageSet ) ) {
			$result->setIndexedTagName_internal( array( 'query', $this->getModuleName() ), 'p' );
		} else {
			$resultPageSet->populateFromTitles( $titles );
		}
	}

	public function getAllowedParams() {
		return array(
			'extension' => array(
				ApiBase::PARAM_DFLT => 'js',
				ApiBase::PARAM_TYPE => array( 'js', 'css' ),
			),
			'namespace' => array(
				ApiBase::PARAM_DFLT => NS_GADGET,
				ApiBase::PARAM_TYPE => 'namespace',
			),
			'prefix' => null,
			'from' => null,
			'limit' => array(
				ApiBase::PARAM_DFLT => 10,
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_MIN => 1,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_BIG1,
				ApiBase::PARAM_MAX2 => ApiBase::LIMIT_BIG2
			),
			'dir' => array(
				ApiBase::PARAM_DFLT => 'ascending',
				ApiBase::PARAM_TYPE => array(
					'ascending',
					'descending'
				)
			),
		);
	}

	public function getDescription() {
		return 'Returns a list of .js/.css pages';
	}

	public function getParamDescription() {
		return array(
			'extension' => 'Search for pages with this extension.',
			'namespace' => 'Search for pages in this namespace.',
			'prefix' => array( 'Search for pages with this prefix (optional).',
			                   'NOTE: Prefix does not include the namespace prefix',
			),
			'from' => 'Start at this page title. NOTE: Does not include the namespace prefix',
			'limit' => 'How many total pages to return.',
			'dir' => 'The direction in which to list',
		);
	}

	public function getExamples() {
		return array(
			'Get a list of .js pages in the MediaWiki namespace:',
			'    api.php?action=query&list=gadgetpages&gpextension=js&gpnamespace=8',
			"Get a list of .css pages in the Catrope's user space:",
			'    api.php?action=query&list=gadgetpages&gpextension=css&gpnamespace=2&gpprefix=Catrope/',
		);
	}
}
