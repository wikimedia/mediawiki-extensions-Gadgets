<?php

class GadgetCssContentHandler extends CssContentHandler {

	public function __construct() {
		parent::__construct( 'GadgetCss' );
	}

	public function canBeUsedOn( Title $title ) {
		return $title->inNamespace( NS_GADGET )
		&& substr( $title->getText(), -4 ) === '.css';
	}

	protected function getContentClass() {
		return 'GadgetCssContent';
	}
}
