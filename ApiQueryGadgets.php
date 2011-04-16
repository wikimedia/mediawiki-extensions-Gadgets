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

class ApiQueryGadgets extends ApiQueryBase {
	private $props;

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'ga' );
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$this->props = array_flip( $params['prop'] );

		$this->applyList( $this->getList() );
	}

	private function getList() {
		$gadgets = Gadget::loadStructuredList();

		$result = array();
		foreach ( $gadgets as $category => $list ) {
			foreach ( $list as $g ) {
				if ( $this->isNeeded( $g ) ) {
					$result[] = $g;
				}
			}
		}
		return $result;
	}

	private function applyList( $gadgets ) {
		$data = array();
		$result = $this->getResult();

		foreach ( $gadgets as $g ) {
			$row = array();
			if ( isset( $this->props['name'] ) ) {
				$row['name'] = $g->getName();
			}
			if ( isset( $this->props['desc'] ) ) {
				$row['desc'] = $g->getDescription();
			}
			if ( isset( $this->props['desc-raw'] ) ) {
				$row['desc-raw'] = $g->getRawDescription();
			}
			if ( isset( $this->props['category'] ) ) {
				$row['category'] = $g->getCategory();
			}
			if ( isset( $this->props['resourceloader'] ) && $g->supportsResourceLoader() ) {
				$row['resourceloader'] = '';
			}
			if ( isset( $this->props['scripts'] ) ) {
				$row['scripts'] = $g->getScripts();
				$result->setIndexedTagName( $row['scripts'], 'script' );
			}
			if ( isset( $this->props['styles'] ) ) {
				$row['styles'] = $g->getStyles();
				$result->setIndexedTagName( $row['styles'], 'style' );
			}
			if ( isset( $this->props['dependencies'] ) ) {
				$row['dependencies'] = $g->getDependencies();
				$result->setIndexedTagName( $row['dependencies'], 'module' );
			}
			if ( isset( $this->props['rights'] ) ) {
				$row['rights'] = $g->getRequiredRights();
				$result->setIndexedTagName( $row['rights'], 'right' );
			}
			if ( isset( $this->props['default'] ) && $g->isOnByDefault() ) {
				$row['default'] = '';
			}
			if ( isset( $this->props['definition'] ) ) {
				$row['definition'] = $g->getDefinition();
			}
			$data[] = $row;
		}
		$result->setIndexedTagName( $data, 'gadget' );
		$result->addValue( 'query', $this->getModuleName(), $data );
	}

	/**
	 * 
	 */
	private function isNeeded( Gadget $gadget ) {
		return true;
	}

	public function getAllowedParams() {
		return array(
			'prop' => array(
				ApiBase::PARAM_DFLT => 'name',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => array(
					'name',
					'desc',
					'desc-raw',
					'category',
					'resourceloader',
					'scripts',
					'styles',
					'dependencies',
					'rights',
					'default',
					'definition',
				),
			),
		);
	}

	public function getDescription() {
		return 'Returns a list of gadgets used on this wiki';
	}

	public function getParamDescription() {
		return array(
			'prop' => array(
				'What gadget information to get:',
				' name           - Internal gadget name',
				' desc           - Gadget description transformed into HTML',
				' desc-raw       - Gadget description in raw wikitext',
				' category       - Internal name of a category gadget belongs to (empty if top-level gadget)',
				' resourceloader - Whether gadget supports ResourceLoader',
				" scripts        - List of gadget's scripts",
				" styles         - List of gadget's styles",
				' dependencies   - List of ResourceLoader modules gadget depends on',
				' rights         - List of rights required to use gadget, if any',
				' default        - Whether gadget is enabled by default',
				' definition     - Line from MediaWiki:Gadgets-definition used to define the gadget',
			),
		);
	}

	protected function getExamples() {
		return array(
			'Get a list of gadgets along with their descriptions:',
			'    api.php?action=query&list=gadgets&gaprop=name|desc',
		);
	}

	public function getVersion() {
		return __CLASS__ . ': $Id:  $';
	}

} 