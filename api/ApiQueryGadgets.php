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
		$categories,
		$neededIds,
		$listAllowed,
		$listEnabled,
		$listShared,
		$language;

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'ga' );
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$this->props = array_flip( $params['prop'] );
		$this->language = $params['language'];
		$this->categories = isset( $params['categories'] )
			? array_flip( $params['categories'] )
			: false;
		$this->neededIds = isset( $params['ids'] )
			? $params['ids']
			: false;
		$this->listAllowed = isset( $params['allowedonly'] ) && $params['allowedonly'];
		$this->listEnabled = isset( $params['enabledonly'] ) && $params['enabledonly'];
		$this->listShared = isset( $params['sharedonly'] ) && $params['sharedonly'];

		$this->getMain()->setCacheMode( $this->listAllowed || $this->listEnabled
			? 'anon-public-user-private' : 'public' );

		$this->applyList( $this->getList() );
	}

	/**
	 * @return array
	 */
	private function getList() {
		$repo = LocalGadgetRepo::singleton();
		$result = array();

		if ( $this->neededIds !== false ) {
			// Get all requested gadgets by id
			$ids = $this->neededIds;
		} else {
			// Get them all
			$ids = $repo->getGadgetIds();
		}

		foreach ( $ids as $id ) {
			$gadget = $repo->getGadget( $id );
			if ( $gadget && $this->isNeeded( $gadget ) ) {
				$result[$id] = $gadget;
			}
		}

		return $result;
	}

	/**
	 * @param Gadget[] $gadgets
	 */
	private function applyList( $gadgets ) {
		$data = array();
		$result = $this->getResult();

		foreach ( $gadgets as $id => $g ) {
			$row = array();
			if ( isset( $this->props['id'] ) ) {
				$row['id'] = $id;
			}
			if ( isset( $this->props['metadata'] ) ) {
				$row['metadata'] = $g->getMetadata();
				$this->setIndexedTagNameForMetadata( $row['metadata'] );
			}
			if ( isset( $this->props['timestamp'] ) ) {
				$context = ResourceLoaderContext::newDummyContext();
				$row['timestamp'] = wfTimestamp( TS_ISO_8601, $g->getModule()->getModifiedTime( $context ) );
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
	 * @param $gadget Gadget
	 *
	 * @return bool
	 */
	private function isNeeded( Gadget $gadget ) {
		$user = $this->getUser();

		return ( !$this->listAllowed || $gadget->isAllowed( $user ) )
			&& ( !$this->listEnabled || $gadget->isEnabled( $user ) ) // @fixme Gadget::isEnabled is undefined
			&& ( !$this->listShared || $gadget->isShared() )
			&& ( !$this->categories || isset( $this->categories[$gadget->getCategory()] ) );
	}

	private function setIndexedTagNameForMetadata( &$metadata ) {
		static $tagNames = array(
			'rights' => 'right',
			'scripts' => 'script',
			'styles' => 'style',
			'dependencies' => 'dependency',
			'messages' => 'message',
		);

		$result = $this->getResult();
		foreach ( $metadata as &$data ) {
			foreach ( $data as $key => &$value ) {
				if ( is_array( $value ) ) {
					$tag = isset( $tagNames[$key] ) ? $tagNames[$key] : $key;
					$result->setIndexedTagName( $value, $tag );
				}
			}
		}
	}

	public function getAllowedParams() {
		return array(
			'prop' => array(
				ApiBase::PARAM_DFLT => 'id|metadata',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => array(
					'id',
					'metadata',
					'timestamp',
					'definitiontimestamp',
					'desc',
					'desc-msgkey',
					'title',
					'title-msgkey',
				),
			),
			'categories' => array(
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => 'string',
			),
			'ids' => array(
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
				' id             - Internal gadget id',
				' metadata       - The gadget metadata',
				' timestamp      - Last changed timestamp of the gadget module, including any files it references',
				' definitiontimestamp - Last changed timestamp of the gadget metadata',
				' desc           - Gadget description translated in the given language and transformed into HTML (can be slow, use only if really needed)',
				' desc-msgkey    - Message key used for the Gadget description',
				' title          - Gadget title translated in the given language',
				' title-msgkey   - Message key used for the Gadget title',
			),
			'language' => "Language code to use for {$p}prop=desc and {$p}prop=title. Defaults to the user language",
			'categories' => 'Gadgets from what categories to retrieve',
			'ids' => 'Id(s) of gadgets to retrieve',
			'allowedonly' => 'List only gadgets allowed to current user',
			'enabledonly' => 'List only gadgets enabled by current user',
			'sharedonly' => 'Only list shared gadgets',
		);
	}

	public function getExamples() {
		$params = $this->getAllowedParams();
		$allProps = implode( '|', $params['prop'][ApiBase::PARAM_TYPE] );
		return array(
			'Get a list of gadgets along with their descriptions:',
			'    api.php?action=query&list=gadgets&gaprop=id|desc',
			'Get a list of gadgets with all possible properties:',
			"    api.php?action=query&list=gadgets&gaprop=$allProps",
			'Get a list of gadgets belonging to category "foo":',
			'    api.php?action=query&list=gadgets&gacategories=foo',
			'Get information about gadgets "foo" and "bar":',
			'    api.php?action=query&list=gadgets&gaids=foo|bar&gaprop=id|desc|metadata',
			'Get a list of gadgets enabled by current user:',
			'    api.php?action=query&list=gadgets&gaenabledonly',
		);
	}
}
