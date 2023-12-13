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

namespace MediaWiki\Extension\Gadgets\Content;

use Content;
use FormatJson;
use JsonContentHandler;
use Linker;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Title\Title;
use ParserOutput;

class GadgetDefinitionContentHandler extends JsonContentHandler {
	public function __construct() {
		parent::__construct( 'GadgetDefinition' );
	}

	/**
	 * @param Title $title
	 * @return bool
	 */
	public function canBeUsedOn( Title $title ) {
		return $title->inNamespace( NS_GADGET_DEFINITION );
	}

	/** @inheritDoc */
	protected function getContentClass() {
		return GadgetDefinitionContent::class;
	}

	public function makeEmptyContent() {
		$class = $this->getContentClass();
		return new $class( FormatJson::encode( $this->getEmptyDefinition(), "\t" ) );
	}

	public function getEmptyDefinition() {
		return [
			'settings' => [
				'category' => '',
			],
			'module' => [
				'pages' => [],
				'dependencies' => [],
			]
		];
	}

	public function getDefaultMetadata() {
		return [
			'settings' => [
				'rights' => [],
				'default' => false,
				'package' => false,
				'requiresES6' => false,
				'hidden' => false,
				'skins' => [],
				'targets' => [ 'desktop', 'mobile' ],
				'actions' => [],
				'namespaces' => [],
				'contentModels' => [],
				'category' => '',
				'supportsUrlLoad' => false,
			],
			'module' => [
				'pages' => [],
				'peers' => [],
				'dependencies' => [],
				'messages' => [],
				'type' => '',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function fillParserOutput(
		Content $content,
		ContentParseParams $cpoParams,
		ParserOutput &$parserOutput
	) {
		'@phan-var GadgetDefinitionContent $content';
		// Create a deep clone. FIXME: unserialize(serialize()) is hacky.
		$data = unserialize( serialize( $content->getData()->getValue() ) );
		if ( $data !== null ) {
			if ( isset( $data->module->pages ) ) {
				foreach ( $data->module->pages as &$page ) {
					$title = Title::makeTitleSafe( NS_GADGET, $page );
					$this->makeLink( $parserOutput, $page, $title );
				}
			}
			if ( isset( $data->module->dependencies ) ) {
				foreach ( $data->module->dependencies as &$dep ) {
					if ( str_starts_with( $dep, 'ext.gadget.' ) ) {
						$gadgetId = explode( 'ext.gadget.', $dep )[ 1 ];
						$title = Title::makeTitleSafe( NS_GADGET_DEFINITION, $gadgetId );
						$this->makeLink( $parserOutput, $dep, $title );
					}
				}
			}
			if ( isset( $data->module->peers ) ) {
				foreach ( $data->module->peers as &$peer ) {
					$title = Title::makeTitleSafe( NS_GADGET_DEFINITION, $peer );
					$this->makeLink( $parserOutput, $peer, $title );
				}
			}
			if ( isset( $data->module->messages ) ) {
				foreach ( $data->module->messages as &$msg ) {
					$title = Title::makeTitleSafe( NS_MEDIAWIKI, $msg );
					$this->makeLink( $parserOutput, $msg, $title );
				}
			}
			if ( isset( $data->settings->category ) && $data->settings->category ) {
				$this->makeLink(
					$parserOutput,
					$data->settings->category,
					Title::makeTitleSafe( NS_MEDIAWIKI, "gadget-section-" . $data->settings->category )
				);
			}
		}

		if ( !$cpoParams->getGenerateHtml() || !$content->isValid() ) {
			$parserOutput->setText( '' );
		} else {
			$parserOutput->setText( $content->rootValueTable( $data ) );
			$parserOutput->addModuleStyles( [ 'mediawiki.content.json' ] );
		}
	}

	/**
	 * Create a link on the page
	 * @param ParserOutput $parserOutput
	 * @param string &$text The text to link
	 * @param Title|null $title Link target title
	 * @return void
	 */
	private function makeLink( ParserOutput $parserOutput, string &$text, ?Title $title ) {
		if ( $title ) {
			$parserOutput->addLink( $title );
			$text = new GadgetDefinitionContentArmor(
				Linker::link( $title, htmlspecialchars( '"' . $text . '"' ) )
			);
		}
	}
}
