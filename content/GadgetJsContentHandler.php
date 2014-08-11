<?php

class GadgetJsContentHandler extends JavaScriptContentHandler {

	public function __construct() {
		parent::__construct( 'GadgetJs' );
	}

	public function canBeUsedOn( Title $title ) {
		return $title->inNamespace( NS_GADGET ); // @todo also check ends with .js?
	}

	protected function getContentClass() {
		return 'GadgetJsContent';
	}
}
