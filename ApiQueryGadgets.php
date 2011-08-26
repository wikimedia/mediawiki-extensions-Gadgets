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
	private $props,
		$category,
		$neededNames,
		$listAllowed,
		$listEnabled;

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'ga' );
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$this->props = array_flip( $params['prop'] );
		$this->categories = isset( $params['categories'] )
			? array_flip( $params['categories'] )
			: false;
		$this->neededNames = isset( $params['names'] )
			? array_flip( $params['names'] )
			: false;
		$this->listAllowed = isset( $params['allowedonly'] ) && $params['allowedonly'];
		$this->listEnabled = isset( $params['enabledonly'] ) && $params['enabledonly'];

		$this->getMain()->setCacheMode( $this->listAllowed || $this->listEnabled
			? 'anon-public-user-private' : 'public' );

		$this->applyList( $this->getList() );
	}

	private function getList() {
		$gadgets = Gadget::loadStructuredList();

		$result = array();
		foreach ( $gadgets as $category => $list ) {
			if ( $this->categories && !isset( $this->categories[$category] ) ) {
				continue;
			}
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
		global $wgUser;

		return ( $this->neededNames === false || isset( $this->neededNames[$gadget->getName()] ) )
			&& ( !$this->listAllowed || $gadget->isAllowed( $wgUser ) )
			&& ( !$this->listEnabled || $gadget->isEnabled( $wgUser ) );
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
			'categories' => array(
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => 'string',
			),
			'names' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
			),
			'allowedonly' => false,
			'enabledonly' => false,
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
				' desc           - Gadget description transformed into HTML (can be slow, use only if really needed)',
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
			'categories' => 'Gadgets from what categories to retrieve',
			'names' => 'Name(s) of gadgets to retrieve',
			'allowedonly' => 'List only gadgets allowed to current user',
			'enabledonly' => 'List only gadgets enabled by current user',
		);
	}

	public function getExamples() {
		$params = $this->getAllowedParams();
		$allProps = implode( '|', $params['prop'][ApiBase::PARAM_TYPE] );
		return array(
			'Get a list of gadgets along with their descriptions:',
			'    api.php?action=query&list=gadgets&gaprop=name|desc',
			'Get a list of gadgets with all possble properties:',
			"    api.php?action=query&list=gadgets&gaprop=$allProps",
			'Get a list of gadgets belonging to caregory "foo":',
			'    api.php?action=query&list=gadgets&gacategories=foo',
			'Get information about gadgets named "foo" and "bar":',
			'    api.php?action=query&list=gadgets&ganames=foo|bar&gaprop=name|desc|category',
			'Get a list of gadgets enabled by current user:',
			'    api.php?action=query&list=gadgets&gaenabledonly',
		);
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
