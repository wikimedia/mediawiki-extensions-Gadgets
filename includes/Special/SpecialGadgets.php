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
use MediaWiki\Content\ContentHandler;
use MediaWiki\Extension\Gadgets\Gadget;
use MediaWiki\Extension\Gadgets\GadgetRepo;
use MediaWiki\Html\Html;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\Language\Language;
use MediaWiki\MainConfigNames;
use MediaWiki\Message\Message;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\Skin\SkinFactory;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;

/**
 * Special:Gadgets renders the data of MediaWiki:Gadgets-definition.
 *
 * @copyright 2007 Daniel Kinzler
 */
class SpecialGadgets extends SpecialPage {
	public function __construct(
		private readonly Language $contentLanguage,
		private readonly GadgetRepo $gadgetRepo,
		private readonly SkinFactory $skinFactory,
	) {
		parent::__construct( 'Gadgets' );
	}

	/**
	 * Return title for Special:Gadgets heading and Special:Specialpages link.
	 *
	 * @return Message
	 */
	public function getDescription() {
		return $this->msg( 'special-gadgets' );
	}

	/**
	 * @param string|null $par Parameters passed to the page
	 */
	public function execute( $par ) {
		$parts = $par !== null ? explode( '/', $par ) : [];

		if ( count( $parts ) >= 2 && $parts[0] === 'export' ) {
			$this->showExportForm( $parts[1] );
		} else {
			$this->showMainForm();
		}
	}

	/**
	 * @param string $gadgetName
	 * @return string
	 */
	private function makeAnchor( $gadgetName ) {
		return 'gadget-' . Sanitizer::escapeIdForAttribute( $gadgetName );
	}

	/**
	 * Displays form showing the list of installed gadgets
	 */
	public function showMainForm() {
		$output = $this->getOutput();
		$this->setHeaders();
		$this->addHelpLink( 'Extension:Gadgets' );
		$output->setPageTitleMsg( $this->msg( 'gadgets-title' ) );
		$output->addWikiMsg( 'gadgets-pagetext' );

		$gadgets = $this->gadgetRepo->getStructuredList();
		if ( !$gadgets ) {
			return;
		}

		$output->disallowUserJs();
		$lang = $this->getLanguage();
		$langSuffix = "";
		if ( !$lang->equals( $this->contentLanguage ) ) {
			$langSuffix = "/" . $lang->getCode();
		}

		$listOpen = false;

		$editDefinitionMessage = $this->getUser()->isAllowed( 'editsitejs' )
			? 'edit'
			: 'viewsource';
		$editInterfaceMessage = $this->getUser()->isAllowed( 'editinterface' )
			? 'gadgets-editdescription'
			: 'gadgets-viewdescription';
		$editInterfaceMessageSection = $this->getUser()->isAllowed( 'editinterface' )
			? 'gadgets-editsectiontitle'
			: 'gadgets-viewsectiontitle';

		$linkRenderer = $this->getLinkRenderer();
		foreach ( $gadgets as $section => $entries ) {
			if ( $section !== false && $section !== '' ) {
				if ( $listOpen ) {
					$output->addHTML( Html::closeElement( 'ul' ) . "\n" );
					$listOpen = false;
				}

				// H2 section heading
				$headingText = $this->msg( "gadget-section-$section" )->parse();
				$output->addHTML( Html::rawElement( 'h2', [], $headingText ) . "\n" );

				// Edit link for the section heading
				$title = Title::makeTitleSafe( NS_MEDIAWIKI, "Gadget-section-$section$langSuffix" );
				$linkTarget = $title
					? $linkRenderer->makeLink( $title, $this->msg( $editInterfaceMessageSection )->text(),
						[], [ 'action' => 'edit' ] )
					: htmlspecialchars( $section );
				$output->addHTML( Html::rawElement( 'p', [],
					$this->msg( 'parentheses' )->rawParams( $linkTarget )->escaped() ) . "\n" );
			}

			/**
			 * @var Gadget $gadget
			 */
			foreach ( $entries as $gadget ) {
				$name = $gadget->getName();
				$title = Title::makeTitleSafe( NS_MEDIAWIKI, "Gadget-{$name}$langSuffix" );
				if ( !$title ) {
					continue;
				}

				$links = [];
				$definitionTitle = $this->gadgetRepo->getGadgetDefinitionTitle( $name );
				if ( $definitionTitle ) {
					$links[] = $linkRenderer->makeLink(
						$definitionTitle,
						$this->msg( $editDefinitionMessage )->text(),
						[],
						[ 'action' => 'edit' ]
					);
				}
				$links[] = $linkRenderer->makeLink(
					$title,
					$this->msg( $editInterfaceMessage )->text(),
					[],
					[ 'action' => 'edit' ]
				);
				$links[] = $linkRenderer->makeLink(
					$this->getPageTitle( "export/{$name}" ),
					$this->msg( 'gadgets-export' )->text()
				);

				$nameHtml = $this->msg( "gadget-{$name}" )->parse();

				if ( !$listOpen ) {
					$listOpen = true;
					$output->addHTML( Html::openElement( 'ul' ) );
				}

				$actionsHtml = '&#160;&#160;' .
					$this->msg( 'parentheses' )->rawParams( $lang->pipeList( $links ) )->escaped();
				$output->addHTML(
					Html::openElement( 'li', [ 'id' => $this->makeAnchor( $name ) ] ) .
						$nameHtml . $actionsHtml
				);
				// Whether the next portion of the list item contents needs
				// a line break between it and the next portion.
				// This is set to false after lists, but true after lines of text.
				$needLineBreakAfter = true;

				// Portion: Show files, dependencies, speers
				if ( $needLineBreakAfter ) {
					$output->addHTML( '<br />' );
				}
				$output->addHTML(
					$this->msg( 'gadgets-uses' )->escaped() .
					$this->msg( 'colon-separator' )->escaped()
				);
				$links = [];
				foreach ( $gadget->getPeers() as $peer ) {
					$links[] = Html::element(
						'a',
						[ 'href' => '#' . $this->makeAnchor( $peer ) ],
						$peer
					);
				}
				foreach ( $gadget->getScriptsAndStyles() as $codePage ) {
					$title = Title::newFromText( $codePage );
					if ( !$title ) {
						continue;
					}
					$links[] = $linkRenderer->makeLink( $title, $title->getText() );
				}
				$output->addHTML( $lang->commaList( $links ) );

				if ( $gadget->isPackaged() ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML( $this->msg( 'gadgets-packaged',
						$this->gadgetRepo->titleWithoutPrefix( $gadget->getScripts()[0], $gadget->getName() ) ) );
					$needLineBreakAfter = true;
				}

				// Portion: Legacy scripts
				if ( $gadget->getLegacyScripts() ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addModuleStyles( 'mediawiki.codex.messagebox.styles' );
					$output->addHTML( Html::errorBox(
						$this->msg( 'gadgets-legacy' )->parse(),
						'',
						'mw-gadget-legacy'
					) );
					$needLineBreakAfter = false;
				}

				if ( $gadget->requiresES6() ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML(
						$this->msg( 'gadgets-requires-es6' )->parse()
					);
					$needLineBreakAfter = true;
				}

				// Portion: Show required rights (optional)
				$rights = [];
				foreach ( $gadget->getRequiredRights() as $right ) {
					$rights[] = Html::element(
						'code',
						[ 'title' => $this->msg( "right-$right" )->plain() ],
						$right
					);
				}
				if ( $rights ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML(
						$this->msg( 'gadgets-required-rights', $lang->commaList( $rights ), count( $rights ) )->parse()
					);
					$needLineBreakAfter = true;
				}

				// Portion: Show required skins (optional)
				$requiredSkins = $gadget->getRequiredSkins();
				$skins = [];
				$validskins = $this->skinFactory->getInstalledSkins();
				foreach ( $requiredSkins as $skinid ) {
					if ( isset( $validskins[$skinid] ) ) {
						$skins[] = $this->msg( "skinname-$skinid" )->plain();
					} else {
						$skins[] = $skinid;
					}
				}
				if ( $skins ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML(
						$this->msg( 'gadgets-required-skins', $lang->commaList( $skins ) )
							->numParams( count( $skins ) )->parse()
					);
					$needLineBreakAfter = true;
				}

				// Portion: Show required actions (optional)
				$actions = [];
				foreach ( $gadget->getRequiredActions() as $action ) {
					$actions[] = Html::element( 'code', [], $action );
				}
				if ( $actions ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML(
						$this->msg( 'gadgets-required-actions', $lang->commaList( $actions ) )
							->numParams( count( $actions ) )->parse()
					);
					$needLineBreakAfter = true;
				}

				// Portion: Show required namespaces (optional)
				$namespaces = $gadget->getRequiredNamespaces();
				if ( $namespaces ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML(
						$this->msg(
							'gadgets-required-namespaces',
							$lang->commaList( array_map( function ( int $ns ) use ( $lang ) {
								return $ns == NS_MAIN
									? $this->msg( 'blanknamespace' )->text()
									: $lang->getFormattedNsText( $ns );
							}, $namespaces ) )
						)->numParams( count( $namespaces ) )->parse()
					);
					$needLineBreakAfter = true;
				}

				// Portion: Show required content models (optional)
				$contentModels = [];
				foreach ( $gadget->getRequiredContentModels() as $model ) {
					$contentModels[] = Html::element(
						'code',
						[ 'title' => ContentHandler::getLocalizedName( $model, $lang ) ],
						$model
					);
				}
				if ( $contentModels ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML(
						$this->msg( 'gadgets-required-contentmodels',
							$lang->commaList( $contentModels ),
							count( $contentModels )
						)->parse()
					);
					$needLineBreakAfter = true;
				}

				// Portion: Show required categories (optional)
				$categories = [];
				foreach ( $gadget->getRequiredCategories() as $category ) {
					$title = Title::makeTitleSafe( NS_CATEGORY, $category );
					$categories[] = $title
						? $linkRenderer->makeLink( $title, $category )
						: htmlspecialchars( $category );
				}
				if ( $categories ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML(
						$this->msg( 'gadgets-required-categories' )
							->rawParams( $lang->commaList( $categories ) )
							->numParams( count( $categories ) )->parse()
					);
					$needLineBreakAfter = true;
				}
				// Show if hidden
				if ( $gadget->isHidden() ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML( $this->msg( 'gadgets-hidden' )->parse() );
					$needLineBreakAfter = true;
				}

				// Show if supports URL load
				if ( $gadget->supportsUrlLoad() ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML( $this->msg( 'gadgets-supports-urlload' )->parse() );
					$needLineBreakAfter = true;
				}

				// Portion: Show on by default (optional)
				if ( $gadget->isOnByDefault() ) {
					if ( $needLineBreakAfter ) {
						$output->addHTML( '<br />' );
					}
					$output->addHTML( $this->msg( 'gadgets-default' )->parse() );
					$needLineBreakAfter = true;
				}

				// Show warnings
				$warnings = $this->gadgetRepo->validationWarnings( $gadget );

				if ( $warnings ) {
					$output->addModuleStyles( 'mediawiki.codex.messagebox.styles' );
					$output->addHTML( Html::warningBox( implode( '<br/>', array_map( static function ( $msg ) {
						return $msg->parse();
					}, $warnings ) ) ) );
					$needLineBreakAfter = false;
				}

				$output->addHTML( Html::closeElement( 'li' ) . "\n" );
			}
		}

		if ( $listOpen ) {
			$output->addHTML( Html::closeElement( 'ul' ) . "\n" );
		}
	}

	/**
	 * Exports a gadget with its dependencies in a serialized form
	 * @param string $gadget Name of gadget to export
	 */
	public function showExportForm( $gadget ) {
		$this->addHelpLink( 'Extension:Gadgets' );
		$output = $this->getOutput();
		try {
			$g = $this->gadgetRepo->getGadget( $gadget );
		} catch ( InvalidArgumentException ) {
			$output->showErrorPage( 'error', 'gadgets-not-found', [ $gadget ] );
			return;
		}

		$this->setHeaders();
		$output->setPageTitleMsg( $this->msg( 'gadgets-export-title' ) );
		$output->addWikiMsg( 'gadgets-export-text', $gadget, $g->getDefinition() );

		$exportList = "MediaWiki:gadget-$gadget\n";
		foreach ( $g->getScriptsAndStyles() as $page ) {
			$exportList .= "$page\n";
		}

		$htmlForm = HTMLForm::factory( 'ooui', [], $this->getContext() );
		$htmlForm
			->setTitle( SpecialPage::getTitleFor( 'Export' ) )
			->addHiddenField( 'pages', $exportList )
			->addHiddenField( 'wpDownload', '1' )
			->addHiddenField( 'templates', '1' )
			->setAction( $this->getConfig()->get( MainConfigNames::Script ) )
			->setMethod( 'get' )
			->setSubmitText( $this->msg( 'gadgets-export-download' )->text() )
			->prepareForm()
			->displayForm( false );
	}

	/**
	 * @inheritDoc
	 */
	protected function getGroupName() {
		return 'wiki';
	}
}
