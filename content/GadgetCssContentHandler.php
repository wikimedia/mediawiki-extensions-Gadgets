<?php

class GadgetCssContentHandler extends CssContentHandler {

	public function __construct() {
		parent::__construct( 'GadgetCss' );
	}

	public function canBeUsedOn( Title $title ) {
		return $title->inNamespace( NS_GADGET ); // @todo also check ends with .css?
	}

	protected function getContentClass() {
		return 'GadgetCssContent';
	}
}
