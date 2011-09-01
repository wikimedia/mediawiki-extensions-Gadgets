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
		$this->language = $params['language'] === null ? null : $params['language'];
		$this->categories = isset( $params['categories'] )
			? array_flip( $params['categories'] )
			: false;
		$this->neededNames = isset( $params['names'] )
			? $params['names']
			: false;
		$this->listAllowed = isset( $params['allowedonly'] ) && $params['allowedonly'];
		$this->listEnabled = isset( $params['enabledonly'] ) && $params['enabledonly'];
		$this->listShared = isset( $params['sharedonly'] ) && $params['sharedonly'];

		$this->getMain()->setCacheMode( $this->listAllowed || $this->listEnabled
			? 'anon-public-user-private' : 'public' );

		$this->applyList( $this->getList() );
	}

	private function getList() {
		$repo = new LocalGadgetRepo( array() );
		$result = array();
		
		if ( $this->neededNames !== false ) {
			// Get all requested gadgets by name
			$names = $this->neededNames;
		} else {
			// Get them all
			$names = $repo->getGadgetNames();
		}
		
		foreach ( $names as $name ) {
			$gadget = $repo->getGadget( $name );
			if ( $gadget && $this->isNeeded( $gadget ) ) {
				$result[$name] = $gadget;
			}
		}
		
		return $result;
	}

	private function applyList( $gadgets ) {
		$data = array();
		$result = $this->getResult();

		foreach ( $gadgets as $name => $g ) {
			$row = array();
			if ( isset( $this->props['name'] ) ) {
				$row['name'] = $name;
			}
			if ( isset( $this->props['json'] ) ) {
				$row['json'] = $g->getJSON();
			}
			if ( isset( $this->props['timestamp'] ) ) {
				$row['timestamp'] = wfTimestamp( TS_ISO_8601, $g->getModule()->getModifiedTime() );
			}
			if ( isset( $this->props['definitiontimestamp'] ) ) {
				$row['definitiontimestamp'] = wfTimestamp( TS_ISO_8601, $g->getTimestamp() );
			}
			if ( isset( $this->props['desc'] ) ) {
				$row['desc'] = $g->getDescriptionMessage( $this->language );
			}
			if ( isset( $this->props['desc-msgkey'] ) ) {
				$row['desc-msgkey'] = $g->getDescriptionMessageKey();
			}
			if ( isset( $this->props['title'] ) ) {
				$row['title'] = $g->getTitleMessage( $this->language );
			}
			if ( isset( $this->props['title-msgkey'] ) ) {
				$row['title-msgkey'] = $g->getTitleMessageKey();
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

		return ( !$this->listAllowed || $gadget->isAllowed( $wgUser ) )
			&& ( !$this->listEnabled || $gadget->isEnabled( $wgUser ) )
			&& ( !$this->listShared || $gadget->isShared() )
			&& ( !$this->categories || isset( $this->categories[$g->getCategory()] ) );
	}

	public function getAllowedParams() {
		return array(
			'prop' => array(
				ApiBase::PARAM_DFLT => 'name|json',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => array(
					'name',
					'json',
					'timestamp',
					'definitiontimestamp',
					'desc',
					'desc-msgkey',
					'title',
					'title-msgkey',
				),
			),
			'language' => null,
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
			'sharedonly' => false,
		);
	}

	public function getDescription() {
		return 'Returns a list of gadgets used on this wiki';
	}

	public function getParamDescription() {
		$p = $this->getModulePrefix();
		return array(
			'prop' => array(
				'What gadget information to get:',
				' name           - Internal gadget name',
				' json           - JSON representation of the gadget metadata.',
				' timestamp      - Last changed timestamp of the gadget module, including any files it references',
				' definitiontimestamp - Last changed timestamp of the gadget metadata',
				' desc           - Gadget description translated in the given language and transformed into HTML (can be slow, use only if really needed)',
				' desc-msgkey    - Message key used for the Gadget description',
				' title          - Gadget title translated in the given language',
				' title-msgkey   - Message key used for the Gadget title',
			),
			'language' => "Language code to use for {$p}prop=desc and {$p}prop=title. Defaults to the user language",
			'categories' => 'Gadgets from what categories to retrieve',
			'names' => 'Name(s) of gadgets to retrieve',
			'allowedonly' => 'List only gadgets allowed to current user',
			'enabledonly' => 'List only gadgets enabled by current user',
			'sharedonly' => 'Only list shared gadgets',
		);
	}

	protected function getExamples() {
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
