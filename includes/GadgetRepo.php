<?php

namespace MediaWiki\Extension\Gadgets;

use InvalidArgumentException;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use Message;
use RequestContext;

abstract class GadgetRepo {

	/**
	 * @var GadgetRepo|null
	 */
	private static $instance;

	/**
	 * @var string
	 */
	protected $titlePrefix;

	/**
	 * Get the ids of the gadgets provided by this repository
	 *
	 * It's possible this could be out of sync with what
	 * getGadget() will return due to caching
	 *
	 * @return string[]
	 */
	abstract public function getGadgetIds(): array;

	/**
	 * Get the Gadget object for a given gadget ID
	 *
	 * @param string $id
	 * @return Gadget
	 * @throws InvalidArgumentException For unregistered ID, used by getStructuredList()
	 */
	abstract public function getGadget( string $id ): Gadget;

	/**
	 * Invalidate any caches based on the provided page (after create, edit, or delete).
	 *
	 * This must be called on create and delete as well (T39228).
	 *
	 * @param LinkTarget $target
	 * @return void
	 */
	public function handlePageUpdate( LinkTarget $target ): void {
	}

	/**
	 * Given a gadget ID, return the title of the page where the gadget is
	 * defined (or null if the given repo does not have per-gadget definition
	 * pages).
	 *
	 * @param string $id
	 * @return Title|null
	 */
	public function getGadgetDefinitionTitle( string $id ): ?Title {
		return null;
	}

	/**
	 * Get a lists of Gadget objects by category
	 *
	 * @return array<string,Gadget[]> `[ 'category' => [ 'name' => $gadget ] ]`
	 */
	public function getStructuredList() {
		$list = [];
		foreach ( $this->getGadgetIds() as $id ) {
			try {
				$gadget = $this->getGadget( $id );
			} catch ( InvalidArgumentException $e ) {
				continue;
			}
			$list[$gadget->getCategory()][$gadget->getName()] = $gadget;
		}

		return $list;
	}

	/**
	 * Get the script file name without the "MediaWiki:Gadget-" or "Gadget:" prefix.
	 * This name is used by the client-side require() so that require("Data.json") resolves
	 * to either "MediaWiki:Gadget-Data.json" or "Gadget:Data.json" depending on the
	 * $wgGadgetsRepoClass configuration, enabling easy migration between the configuration modes.
	 *
	 * @param string $titleText
	 * @return string
	 */
	public function titleWithoutPrefix( string $titleText ): string {
		$numReplaces = 1; // there will only one occurrence of the prefix
		return str_replace( $this->titlePrefix, '', $titleText, $numReplaces );
	}

	/**
	 * @param Gadget $gadget
	 * @return Message[]
	 */
	public function validationWarnings( Gadget $gadget ): array {
		// Basic checks local to the gadget definition
		$warningMsgKeys = $gadget->getValidationWarnings();
		$warnings = array_map( static function ( $warningMsgKey ) {
			return wfMessage( $warningMsgKey );
		}, $warningMsgKeys );

		// Check for invalid values in namespaces, targets and contentModels
		$this->checkInvalidLoadConditions( $gadget, 'namespaces', $warnings );
		$this->checkInvalidLoadConditions( $gadget, 'targets', $warnings );
		$this->checkInvalidLoadConditions( $gadget, 'contentModels', $warnings );

		// Peer gadgets not being styles-only gadgets, or not being defined at all
		foreach ( $gadget->getPeers() as $peer ) {
			try {
				$peerGadget = $this->getGadget( $peer );
				if ( $peerGadget->getType() !== 'styles' ) {
					$warnings[] = wfMessage( "gadgets-validate-invalidpeer", $peer );
				}
			} catch ( InvalidArgumentException $ex ) {
				$warnings[] = wfMessage( "gadgets-validate-nopeer", $peer );
			}
		}

		// Check that the gadget pages exist and are of the right content model
		$warnings = array_merge(
			$warnings,
			$this->checkTitles( $gadget->getScripts(), CONTENT_MODEL_JAVASCRIPT,
				"gadgets-validate-invalidjs" ),
			$this->checkTitles( $gadget->getStyles(), CONTENT_MODEL_CSS,
				"gadgets-validate-invalidcss" ),
			$this->checkTitles( $gadget->getJSONs(), CONTENT_MODEL_JSON,
				"gadgets-validate-invalidjson" )
		);

		return $warnings;
	}

	/**
	 * Check titles used in gadget to verify existence and correct content model.
	 * @param array $pages
	 * @param string $expectedContentModel
	 * @param string $msg
	 * @return Message[]
	 */
	private function checkTitles( array $pages, string $expectedContentModel, string $msg ): array {
		$warnings = [];
		foreach ( $pages as $pageName ) {
			$title = Title::newFromText( $pageName );
			if ( !$title ) {
				$warnings[] = wfMessage( "gadgets-validate-invalidtitle", $pageName );
				continue;
			}
			if ( !$title->exists() ) {
				$warnings[] = wfMessage( "gadgets-validate-nopage", $pageName );
				continue;
			}
			$contentModel = $title->getContentModel();
			if ( $contentModel !== $expectedContentModel ) {
				$warnings[] = wfMessage( $msg, $pageName, $contentModel );
			}
		}
		return $warnings;
	}

	/**
	 * @param Gadget $gadget
	 * @param string $condition
	 * @param Message[] &$warnings
	 */
	private function checkInvalidLoadConditions( Gadget $gadget, string $condition, array &$warnings ) {
		switch ( $condition ) {
			case 'namespaces':
				$nsInfo = MediaWikiServices::getInstance()->getNamespaceInfo();
				$this->maybeAddWarnings( $gadget->getRequiredNamespaces(),
					static function ( $ns ) use ( $nsInfo ) {
						return !$nsInfo->exists( $ns );
					}, $warnings, "gadgets-validate-invalidnamespaces"
				);
				break;

			case 'targets':
				$this->maybeAddWarnings( $gadget->toArray()['targets'],
					static function ( $target ) {
						return $target !== 'mobile' && $target !== 'desktop';
					}, $warnings, "gadgets-validate-invalidtargets"
				);
				break;

			case 'contentModels':
				$contentHandlerFactory = MediaWikiServices::getInstance()->getContentHandlerFactory();
				$this->maybeAddWarnings( $gadget->getRequiredContentModels(),
					static function ( $model ) use ( $contentHandlerFactory ) {
						return !$contentHandlerFactory->isDefinedModel( $model );
					}, $warnings, "gadgets-validate-invalidcontentmodels"
				);
				break;
			default:
		}
	}

	/**
	 * Iterate over the given $entries, for each check if it is invalid using $isInvalid predicate,
	 * and if so add the $message to $warnings.
	 *
	 * @param array $entries
	 * @param callable $isInvalid
	 * @param array &$warnings
	 * @param string $message
	 */
	private function maybeAddWarnings( array $entries, callable $isInvalid, array &$warnings, string $message ) {
		$lang = RequestContext::getMain()->getLanguage();
		$invalidEntries = [];
		foreach ( $entries as $entry ) {
			if ( $isInvalid( $entry ) ) {
				$invalidEntries[] = $entry;
			}
		}
		if ( count( $invalidEntries ) ) {
			$warnings[] = wfMessage( $message, $lang->commaList( $invalidEntries ), count( $invalidEntries ) );
		}
	}

	/**
	 * Get the configured default GadgetRepo.
	 *
	 * @deprecated Use the GadgetsRepo service instead
	 * @return GadgetRepo
	 */
	public static function singleton() {
		if ( self::$instance === null ) {
			return MediaWikiServices::getInstance()->getService( 'GadgetsRepo' );
		}
		return self::$instance;
	}

	/**
	 * Should only be used by unit tests
	 *
	 * @deprecated Use the GadgetsRepo service instead
	 * @param GadgetRepo|null $repo
	 */
	public static function setSingleton( $repo = null ) {
		self::$instance = $repo;
	}
}
