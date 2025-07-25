<?php

namespace MediaWiki\Extension\Gadgets;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader as RL;

/**
 * Class representing a list of resources for one gadget, basically a wrapper
 * around the Gadget class.
 */
class GadgetResourceLoaderModule extends RL\WikiModule {
	/**
	 * @var string
	 */
	private $id;

	/**
	 * @var Gadget
	 */
	private $gadget;

	public function __construct( array $options ) {
		$this->id = $options['id'];
	}

	/**
	 * @return Gadget instance this module is about
	 */
	private function getGadget() {
		if ( !$this->gadget ) {
			/** @var GadgetRepo $repo */
			$repo = MediaWikiServices::getInstance()->getService( 'GadgetsRepo' );
			try {
				$this->gadget = $repo->getGadget( $this->id );
			} catch ( InvalidArgumentException ) {
				// Fallback to a placeholder object...
				$this->gadget = Gadget::newEmptyGadget( $this->id );
			}
		}

		return $this->gadget;
	}

	/**
	 * @param RL\Context $context
	 * @return array
	 */
	protected function getPages( RL\Context $context ) {
		$gadget = $this->getGadget();
		$pages = [];

		foreach ( $gadget->getStyles() as $style ) {
			$pages[$style] = [ 'type' => 'style' ];
		}

		if ( $gadget->supportsResourceLoader() ) {
			foreach ( $gadget->getScripts() as $script ) {
				$pages[$script] = [ 'type' => 'script' ];
			}
			if ( $gadget->isPackaged() ) {
				foreach ( $gadget->getVues() as $vue ) {
					$pages[$vue] = [ 'type' => 'script-vue' ];
				}
				foreach ( $gadget->getJSONs() as $json ) {
					$pages[$json] = [ 'type' => 'data' ];
				}
			}
		}

		return $pages;
	}

	/**
	 * @inheritDoc
	 */
	public function getScript( RL\Context $context ) {
		$module = parent::getScript( $context );

		if ( $this->isPackaged() && $this->gadget->getCodexIcons() ) {
			// Add codex icons to the gadget module
			$module['files']['icons.json'] = [
				'type' => 'data',
				'content' => RL\CodexModule::getIcons( $context, $this->getConfig(), $this->gadget->getCodexIcons() ),
			];
		}
		return $module;
	}

	/**
	 * @param string $titleText
	 * @return string
	 */
	public function getRequireKey( $titleText ): string {
		/** @var GadgetRepo $repo */
		$repo = MediaWikiServices::getInstance()->getService( 'GadgetsRepo' );
		return $repo->titleWithoutPrefix( $titleText, $this->id );
	}

	/**
	 * @param string $fileName
	 * @param string $contents
	 * @return string
	 */
	protected function validateScriptFile( $fileName, $contents ) {
		// Temporary solution to support gadgets in ES6 by disabling validation
		// for them and putting them in a separate resource group to avoid a syntax error in them
		// from corrupting core/extension-loaded scripts or other non-ES6 gadgets.
		if ( $this->requiresES6() ) {
			return $contents;
		}
		return parent::validateScriptFile( $fileName, $contents );
	}

	/**
	 * Returns whether this gadget is packaged.
	 */
	public function isPackaged(): bool {
		return $this->getGadget()->isPackaged();
	}

	/**
	 * @param RL\Context|null $context
	 * @return string[] Names of resources this module depends on
	 */
	public function getDependencies( ?RL\Context $context = null ) {
		return $this->getGadget()->getDependencies();
	}

	/**
	 * @return string RL\Module::LOAD_STYLES or RL\Module::LOAD_GENERAL
	 */
	public function getType() {
		return $this->getGadget()->getType() === 'styles'
			? RL\Module::LOAD_STYLES
			: RL\Module::LOAD_GENERAL;
	}

	/** @inheritDoc */
	public function getMessages() {
		return $this->getGadget()->getMessages();
	}

	/** @inheritDoc */
	public function getSkins(): ?array {
		return $this->getGadget()->getRequiredSkins() ?: null;
	}

	/** @inheritDoc */
	public function requiresES6(): bool {
		return $this->getGadget()->requiresES6();
	}

	/** @inheritDoc */
	public function getGroup() {
		return $this->requiresES6() ? 'es6-gadget' : self::GROUP_SITE;
	}
}
